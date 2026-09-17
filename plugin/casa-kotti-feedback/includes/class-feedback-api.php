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
	/**
	 * Hook REST routes.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register endpoints.
	 */
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

	/**
	 * Public fragrance list.
	 *
	 * @return WP_REST_Response
	 */
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

	/**
	 * Accept a feedback payload.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
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

		$validated['uuid']       = wp_generate_uuid4();
		$validated['created_at'] = current_time( 'mysql', true );
		$validated['ip_hash']    = CKF_Security::ip_hash();
		$validated['user_agent'] = CKF_Security::user_agent();

		$id = CKF_Database::insert_feedback( $validated );
		if ( ! $id ) {
			return new WP_Error( 'ckf_save', __( 'Não conseguimos enviar sua avaliação agora. Tente novamente.', 'casa-kotti-feedback' ), array( 'status' => 500 ) );
		}

		/**
		 * After a feedback row is stored. Reserved for CRM, mail, coupons.
		 *
		 * @param int   $id   Inserted ID.
		 * @param array $data Sanitized row.
		 */
		do_action( 'casa_kotti_feedback_saved', $id, $validated );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Obrigado por compartilhar.', 'casa-kotti-feedback' ),
			)
		);
	}

	/**
	 * Server-side field validation.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	private static function validate( WP_REST_Request $request ) {
		$products = ckf_products();
		$product  = sanitize_key( (string) $request->get_param( 'product' ) );
		if ( ! isset( $products[ $product ] ) ) {
			return new WP_Error( 'ckf_product', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
		}

		$slug      = sanitize_title( (string) $request->get_param( 'fragrance' ) );
		$fragrance = CKF_Database::fragrance_by_slug( $slug );
		if ( ! $fragrance ) {
			return new WP_Error( 'ckf_fragrance', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
		}
		if ( 'active' !== $fragrance->status ) {
			return new WP_Error( 'ckf_fragrance', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
		}

		$overall = absint( $request->get_param( 'overall_rating' ) );
		$pres    = absint( $request->get_param( 'presentation_rating' ) );
		if ( $overall < 1 || $overall > 5 || $pres < 1 || $pres > 5 ) {
			return new WP_Error( 'ckf_rating', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
		}

		$intensity = sanitize_key( (string) $request->get_param( 'intensity' ) );
		if ( ! isset( ckf_intensity_options()[ $intensity ] ) ) {
			return new WP_Error( 'ckf_intensity', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
		}

		$performance = sanitize_key( (string) $request->get_param( 'performance' ) );
		if ( ! isset( ckf_performance_options()[ $performance ] ) ) {
			return new WP_Error( 'ckf_performance', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
		}

		$repurchase = sanitize_key( (string) $request->get_param( 'repurchase_intent' ) );
		if ( ! isset( ckf_repurchase_options()[ $repurchase ] ) ) {
			return new WP_Error( 'ckf_repurchase', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
		}

		$nps = $request->get_param( 'nps_score' );
		if ( ! is_numeric( $nps ) ) {
			return new WP_Error( 'ckf_nps', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
		}
		$nps = (int) $nps;
		if ( $nps < 0 || $nps > 10 ) {
			return new WP_Error( 'ckf_nps', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
		}

		$specific_key = sanitize_key( (string) $request->get_param( 'product_specific_answer' ) );
		$specific_ok  = '';
		$target       = $product;
		if ( 'refil' === $product ) {
			$refil_for = sanitize_key( (string) $request->get_param( 'refil_target' ) );
			if ( 'difusor' === $refil_for ) {
				$target = 'difusor';
			} elseif ( 'outro' === $refil_for ) {
				$target = '';
			} else {
				return new WP_Error( 'ckf_refil', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
			}
		}

		$specific_map = ckf_product_specific();
		if ( $target && isset( $specific_map[ $target ] ) ) {
			if ( ! isset( $specific_map[ $target ]['options'][ $specific_key ] ) ) {
				return new WP_Error( 'ckf_specific', __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
			}
			$specific_ok = $specific_key;
		}

		$email = sanitize_email( (string) $request->get_param( 'customer_email' ) );
		if ( $email && ! is_email( $email ) ) {
			return new WP_Error( 'ckf_email', __( 'Digite um e-mail válido.', 'casa-kotti-feedback' ), array( 'status' => 400 ) );
		}

		$consent = $request->get_param( 'marketing_consent' ) ? 1 : 0;

		return array(
			'product'                  => $product,
			'fragrance'                => $fragrance->name,
			'fragrance_slug'           => $fragrance->slug,
			'overall_rating'           => $overall,
			'intensity'                => $intensity,
			'product_specific_answer'  => $specific_ok,
			'performance'              => $performance,
			'presentation_rating'      => $pres,
			'repurchase_intent'        => $repurchase,
			'nps_score'                => $nps,
			'improvement_comment'      => sanitize_textarea_field( (string) $request->get_param( 'improvement_comment' ) ),
			'positive_comment'         => sanitize_textarea_field( (string) $request->get_param( 'positive_comment' ) ),
			'customer_name'            => sanitize_text_field( (string) $request->get_param( 'customer_name' ) ),
			'customer_email'           => $email,
			'marketing_consent'        => $consent,
			'source'                   => CKF_Security::sanitize_token( (string) $request->get_param( 'source' ) ),
			'campaign'                 => CKF_Security::sanitize_token( (string) $request->get_param( 'campaign' ) ),
			'product_code'             => CKF_Security::sanitize_token( (string) $request->get_param( 'product_code' ) ),
			'batch'                    => CKF_Security::sanitize_token( (string) $request->get_param( 'batch' ) ),
		);
	}
}
