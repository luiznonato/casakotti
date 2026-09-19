<?php
/**
 * REST API for public feedback.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_API {
	const TEXT_MAX     = 190;
	const TEXTAREA_MAX = 4000;

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			'casa-kotti/v1',
			'/feedback',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'casa-kotti/v1',
			'/fragrances',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'list_fragrances' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function list_fragrances() {
		$items = array();
		foreach ( CKF_Database::active_fragrances() as $row ) {
			$items[] = array(
				'slug' => $row->slug,
				'name' => $row->name,
			);
		}
		return rest_ensure_response( array( 'success' => true, 'fragrances' => $items ) );
	}

	public static function create( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce ) {
			$nonce = (string) $request->get_param( 'nonce' );
		}
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'ckf_nonce', __( 'Atualize a página e tente novamente.', 'casa-kotti-feedback' ), array( 'status' => 403 ) );
		}

		$honeypot = (string) $request->get_param( 'website' );
		if ( '' !== $honeypot ) {
			return new WP_Error( 'ckf_spam', __( 'Não conseguimos enviar sua avaliação agora. Tente novamente.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
		}

		if ( ! CKF_Security::rate_limit_allows() ) {
			return new WP_Error( 'ckf_rate', __( 'Não conseguimos enviar sua avaliação agora. Tente novamente.', 'casa-kotti-feedback' ), array( 'status' => 429 ) );
		}

		$validated = self::validate( $request );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$row                 = $validated['row'];
		$row['uuid']         = wp_generate_uuid4();
		$row['created_at']   = current_time( 'mysql', true );
		$row['ip_hash']      = CKF_Security::ip_hash();
		$row['user_agent']   = CKF_Security::user_agent();

		$id = CKF_Database::insert_feedback( $row );
		if ( ! $id ) {
			return new WP_Error( 'ckf_save', __( 'Não conseguimos enviar sua avaliação agora. Tente novamente.', 'casa-kotti-feedback' ), array( 'status' => 500 ) );
		}

		CKF_Answers::insert_many( $id, $validated['answers'] );

		do_action( 'casa_kotti_feedback_saved', $id, $row );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Obrigado por compartilhar.', 'casa-kotti-feedback' ),
			)
		);
	}

	/**
	 * Validate dynamic answers and build denormalized row.
	 *
	 * Inapplicable answers are ignored. Missing required applicable answers fail.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	private static function validate( WP_REST_Request $request ) {
		$incoming = $request->get_param( 'answers' );
		if ( ! is_array( $incoming ) ) {
			$incoming = array();
		}
		foreach ( array( 'product', 'fragrance', 'overall_rating', 'intensity', 'refil_target', 'performance', 'presentation_rating', 'repurchase_intent', 'nps_score', 'improvement_comment', 'positive_comment', 'customer_name', 'customer_email', 'marketing_consent', 'product_specific_answer' ) as $legacy ) {
			if ( ! isset( $incoming[ $legacy ] ) && null !== $request->get_param( $legacy ) && '' !== $request->get_param( $legacy ) ) {
				$incoming[ $legacy ] = $request->get_param( $legacy );
			}
		}
		if ( ! empty( $incoming['product'] ) ) {
			$mapped = CKF_Security::sanitize_product_param( $incoming['product'] );
			if ( $mapped ) {
				$incoming['product'] = $mapped;
			}
		}

		$definition = CKF_Questions::public_definition();
		$collected  = array();
		foreach ( $definition as $question ) {
			if ( 'info' === $question['type'] ) {
				continue;
			}
			$slug = $question['slug'];
			if ( ! CKF_Conditions::applies( $question, $incoming ) ) {
				continue;
			}
			$raw = isset( $incoming[ $slug ] ) ? $incoming[ $slug ] : '';
			$parsed = self::parse_answer( $question, $raw );
			if ( is_wp_error( $parsed ) ) {
				return $parsed;
			}
			if ( $question['required'] && self::is_empty( $parsed ) ) {
				return new WP_Error( 'ckf_required', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
			}
			$collected[ $slug ] = array(
				'question' => $question,
				'values'   => $parsed,
			);
		}

		$row = array(
			'product'                 => '',
			'fragrance'               => '',
			'fragrance_slug'          => '',
			'overall_rating'          => 0,
			'intensity'               => '',
			'product_specific_answer' => '',
			'performance'             => '',
			'presentation_rating'     => 0,
			'repurchase_intent'       => '',
			'nps_score'               => 0,
			'improvement_comment'     => '',
			'positive_comment'        => '',
			'customer_name'           => '',
			'customer_email'          => '',
			'marketing_consent'       => 0,
			'source'                  => CKF_Security::sanitize_token( (string) $request->get_param( 'source' ) ),
			'campaign'                => CKF_Security::sanitize_token( (string) $request->get_param( 'campaign' ) ),
			'product_code'            => CKF_Security::sanitize_token( (string) $request->get_param( 'product_code' ) ),
			'batch'                   => CKF_Security::sanitize_token( (string) $request->get_param( 'batch' ) ),
		);

		$answer_rows = array();
		foreach ( $collected as $slug => $item ) {
			$question = $item['question'];
			foreach ( $item['values'] as $value ) {
				$answer_rows[] = array(
					'question_id'   => $question['id'],
					'question_slug' => $slug,
					'answer_value'  => is_string( $value ) ? $value : (string) $value,
					'answer_text'   => is_string( $value ) ? $value : (string) $value,
				);
			}
			$first = isset( $item['values'][0] ) ? $item['values'][0] : '';
			if ( 'fragrance' === $slug && $first ) {
				$frag = CKF_Database::fragrance_by_slug( $first );
				if ( $frag ) {
					$row['fragrance']      = $frag->name;
					$row['fragrance_slug'] = $frag->slug;
				}
			} elseif ( isset( CKF_Questions::SYSTEM_COLUMNS[ $slug ] ) ) {
				$col = CKF_Questions::SYSTEM_COLUMNS[ $slug ];
				if ( 'marketing_consent' === $col ) {
					$row[ $col ] = in_array( (string) $first, array( '1', 'sim', 'yes' ), true ) ? 1 : 0;
				} elseif ( in_array( $col, array( 'overall_rating', 'presentation_rating', 'nps_score' ), true ) ) {
					$row[ $col ] = (int) $first;
				} else {
					$row[ $col ] = (string) $first;
				}
			}
		}

		return array(
			'row'     => $row,
			'answers' => $answer_rows,
		);
	}

	private static function is_empty( $parsed ) {
		return ! $parsed || ( 1 === count( $parsed ) && '' === (string) $parsed[0] );
	}

	/**
	 * @param array $question Question.
	 * @param mixed $raw      Incoming value.
	 * @return array|WP_Error
	 */
	private static function parse_answer( $question, $raw ) {
		$type     = $question['type'];
		$settings = $question['settings'];
		$options  = isset( $question['options'] ) ? $question['options'] : array();
		$allowed  = wp_list_pluck( $options, 'value' );

		if ( 'multi_choice' === $type ) {
			$values = is_array( $raw ) ? $raw : ( '' === $raw ? array() : array( $raw ) );
			$clean  = array();
			foreach ( $values as $value ) {
				$value = sanitize_title( (string) $value );
				if ( ! in_array( $value, $allowed, true ) ) {
					return new WP_Error( 'ckf_option', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
				}
				$clean[] = $value;
			}
			if ( count( $clean ) > count( $allowed ) ) {
				return new WP_Error( 'ckf_option', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
			}
			return $clean;
		}

		if ( in_array( $type, array( 'single_choice', 'radio', 'select' ), true ) ) {
			$value = sanitize_title( is_array( $raw ) ? (string) reset( $raw ) : (string) $raw );
			if ( '' === $value ) {
				return array();
			}
			if ( ! in_array( $value, $allowed, true ) ) {
				return new WP_Error( 'ckf_option', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
			}
			return array( $value );
		}

		if ( 'yes_no' === $type ) {
			$value = is_array( $raw ) ? (string) reset( $raw ) : (string) $raw;
			if ( ! empty( $settings['ui'] ) && 'checkbox' === $settings['ui'] ) {
				return array( $value ? '1' : '' );
			}
			$value = sanitize_title( $value );
			if ( '' === $value ) {
				return array();
			}
			if ( ! in_array( $value, array( 'sim', 'nao', '1', '0' ), true ) ) {
				return new WP_Error( 'ckf_option', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
			}
			return array( $value );
		}

		if ( 'stars' === $type || 'scale' === $type || 'number' === $type ) {
			if ( '' === $raw || null === $raw ) {
				return array();
			}
			if ( ! is_numeric( $raw ) ) {
				return new WP_Error( 'ckf_number', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
			}
			$num = 'number' === $type ? (float) $raw : (int) $raw;
			$min = isset( $settings['min'] ) ? (float) $settings['min'] : ( 'scale' === $type ? 0 : 1 );
			$max = isset( $settings['max'] ) ? (float) $settings['max'] : ( 'scale' === $type ? 10 : 5 );
			if ( $num < $min || $num > $max ) {
				return new WP_Error( 'ckf_number', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
			}
			return array( (string) $num );
		}

		if ( 'email' === $type ) {
			$email = sanitize_email( (string) $raw );
			if ( $email && ! is_email( $email ) ) {
				return new WP_Error( 'ckf_email', __( 'Digite um e-mail válido.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
			}
			return $email ? array( $email ) : array();
		}

		$text = 'textarea' === $type ? sanitize_textarea_field( (string) $raw ) : sanitize_text_field( (string) $raw );
		$cap  = 'textarea' === $type ? self::TEXTAREA_MAX : self::TEXT_MAX;
		if ( isset( $settings['max_length'] ) ) {
			$cap = min( $cap, absint( $settings['max_length'] ) );
		}
		if ( strlen( $text ) > $cap ) {
			$text = substr( $text, 0, $cap );
		}
		return '' === $text ? array() : array( $text );
	}
}
