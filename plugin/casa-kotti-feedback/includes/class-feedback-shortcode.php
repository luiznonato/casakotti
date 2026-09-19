<?php
/**
 * Public shortcode and assets.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Shortcode {
	/**
	 * Register shortcode and enqueue hooks.
	 */
	public static function init() {
		add_shortcode( 'casa_kotti_feedback', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * Whether the current singular view contains the shortcode.
	 *
	 * @return bool
	 */
	public static function on_feedback_page() {
		if ( ! is_singular() ) {
			return false;
		}
		global $post;
		return $post && has_shortcode( $post->post_content, 'casa_kotti_feedback' );
	}

	/**
	 * Body class for hiding duplicate page titles.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public static function body_class( $classes ) {
		if ( self::on_feedback_page() ) {
			$classes[] = 'ck-feedback-page';
		}
		return $classes;
	}

	/**
	 * Load CSS/JS only on shortcode pages.
	 */
	public static function enqueue() {
		if ( ! self::on_feedback_page() ) {
			return;
		}
		self::enqueue_public_assets();
	}

	public static function enqueue_public_assets() {
		$style_deps = wp_style_is( 'casa-kotti-main', 'registered' ) ? array( 'casa-kotti-main' ) : array();
		wp_enqueue_style(
			'casa-kotti-feedback',
			CKF_URL . 'public/feedback.css',
			$style_deps,
			CKF_VERSION
		);
		$font_url = CKF_URL . 'public/fonts/montserrat-latin.woff2';
		wp_add_inline_style(
			'casa-kotti-feedback',
			'@font-face{font-family:"Montserrat CK";font-style:normal;font-weight:100 900;font-display:swap;src:url("' . esc_url( $font_url ) . '") format("woff2");}'
			. '@font-face{font-family:"Montserrat";font-style:normal;font-weight:100 900;font-display:swap;src:url("' . esc_url( $font_url ) . '") format("woff2");}'
		);
		wp_enqueue_script(
			'casa-kotti-feedback-validate',
			CKF_URL . 'public/ckf-validate.js',
			array(),
			CKF_VERSION,
			true
		);
		wp_enqueue_script(
			'casa-kotti-feedback-conditions',
			CKF_URL . 'public/ckf-conditions.js',
			array(),
			CKF_VERSION,
			true
		);
		wp_enqueue_script(
			'casa-kotti-feedback',
			CKF_URL . 'public/feedback.js',
			array( 'casa-kotti-feedback-validate', 'casa-kotti-feedback-conditions' ),
			CKF_VERSION,
			true
		);

		$pre_product = CKF_Security::sanitize_product_param( isset( $_GET['produto'] ) ? wp_unslash( $_GET['produto'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$pre_frag    = sanitize_title( isset( $_GET['fragrancia'] ) ? wp_unslash( $_GET['fragrancia'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$pre_batch   = CKF_Security::sanitize_token( isset( $_GET['lote'] ) ? wp_unslash( $_GET['lote'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$pre_source  = CKF_Security::sanitize_token( isset( $_GET['origem'] ) ? wp_unslash( $_GET['origem'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$pre_camp    = CKF_Security::sanitize_token( isset( $_GET['campanha'] ) ? wp_unslash( $_GET['campanha'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$frags = array();
		foreach ( CKF_Database::active_fragrances() as $row ) {
			$frags[] = array(
				'slug' => $row->slug,
				'name' => $row->name,
			);
		}

		$i18n           = CKF_Validate::messages();
		$i18n['submit'] = __( 'Enviar', 'casa-kotti-feedback' );

		wp_localize_script(
			'casa-kotti-feedback',
			'ckfForm',
			array(
				'restUrl'    => esc_url_raw( rest_url( 'casa-kotti/v1/feedback' ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'homeUrl'    => home_url( '/' ),
				'products'   => ckf_products(),
				'fragrances' => $frags,
				'prefill'    => array(
					'product'   => $pre_product,
					'fragrance' => $pre_frag,
					'batch'     => $pre_batch,
					'source'    => $pre_source,
					'campaign'  => $pre_camp,
				),
				'i18n'       => $i18n,
			)
		);
	}

	/**
	 * Render the questionnaire.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$atts    = shortcode_atts(
			array(
				'slug'    => '',
				'preview' => '',
			),
			$atts,
			'casa_kotti_feedback'
		);
		$survey  = CKF_Surveys::resolve_public( $atts['slug'] );
		$preview = ! empty( $atts['preview'] ) || ( is_admin() && ! wp_doing_ajax() );
		if ( ! $survey ) {
			if ( current_user_can( 'manage_options' ) ) {
				return '<p class="ck-feedback__error">' . esc_html__( 'Questionário não encontrado ou inativo.', 'casa-kotti-feedback' ) . '</p>';
			}
			return '';
		}
		$ckf_survey  = $survey;
		$ckf_preview = $preview;
		ob_start();
		include CKF_DIR . 'public/feedback-form.php';
		return ob_get_clean();
	}
}
