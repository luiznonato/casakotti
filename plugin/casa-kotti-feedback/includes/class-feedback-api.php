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
		if ( $request->get_param( 'preview' ) ) {
			return new WP_Error( 'ckf_preview', __( 'Pré-visualização não envia respostas.', 'casa-kotti-feedback' ), array( 'status' => 403 ) );
		}

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

		$incoming = $request->get_param( 'answers' );
		if ( is_array( $incoming ) && count( $incoming ) > 80 ) {
			return new WP_Error( 'ckf_payload', __( 'Não conseguimos enviar sua avaliação agora. Tente novamente.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
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

		$survey = null;
		$sid    = absint( $request->get_param( 'survey_id' ) );
		$sslug  = sanitize_title( (string) $request->get_param( 'survey' ) );
		if ( $sid ) {
			$survey = CKF_Surveys::get( $sid );
		} elseif ( $sslug ) {
			$survey = CKF_Surveys::by_slug( $sslug );
		} else {
			$survey = CKF_Surveys::default_row();
		}
		if ( ! $survey || 'active' !== $survey->status ) {
			return new WP_Error( 'ckf_survey', __( 'Questionário indisponível.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
		}

		$wizard     = CKF_Questions::public_wizard( (int) $survey->id );
		$definition = $wizard['questions'];
		$by_slug    = array();
		foreach ( $definition as $question ) {
			$by_slug[ $question['slug'] ] = $question;
		}

		$allowed_incoming = array();
		foreach ( $incoming as $key => $value ) {
			$slug = sanitize_title( (string) $key );
			if ( isset( $by_slug[ $slug ] ) ) {
				$allowed_incoming[ $slug ] = $value;
			}
		}
		$incoming = $allowed_incoming;

		$opts       = array();
		$applicable = CKF_Conditions::applicable_on_route( $wizard['steps'], $definition, $incoming, $opts );
		$collected  = array();
		foreach ( $applicable as $slug => $question ) {
			$raw    = isset( $incoming[ $slug ] ) ? $incoming[ $slug ] : '';
			$parsed = CKF_Validate::answer( $question, $raw );
			if ( ! $parsed['ok'] && ! empty( $question['settings']['error_message'] ) ) {
				$parsed['error'] = $question['settings']['error_message'];
			}
			if ( ! $parsed['ok'] ) {
				return new WP_Error(
					'ckf_invalid',
					$parsed['error'],
					array(
						'status' => 400,
						'slug'   => $slug,
					)
				);
			}
			$collected[ $slug ] = array(
				'question' => $question,
				'values'   => $parsed['values'],
			);
		}

		$row = array(
			'survey_id'               => (int) $survey->id,
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
			'schema_version'          => CKF_VERSION,
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
					'field_type'    => $question['type'],
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

}
