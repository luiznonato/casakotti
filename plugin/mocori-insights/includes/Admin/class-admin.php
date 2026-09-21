<?php
/**
 * Admin UI.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin.
 */
class Mocori_Insights_Admin {
	/**
	 * Plugin.
	 *
	 * @var Mocori_Insights_Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Mocori_Insights_Plugin $plugin Plugin.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Hooks.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_init', array( $this, 'handle_post' ) );
		add_action( 'admin_post_mocori_insights_wipe', array( $this, 'wipe' ) );
	}

	/**
	 * Menu.
	 */
	public function menu() {
		$cap = Mocori_Insights_Capabilities::VIEW;
		add_menu_page(
			__( 'Mocori Insights', 'mocori-insights' ),
			__( 'Mocori Insights', 'mocori-insights' ),
			$cap,
			'mocori-insights',
			array( $this, 'render' ),
			'dashicons-chart-area',
			3
		);

		$pages = array(
			'mocori-insights'             => __( 'Dashboard', 'mocori-insights' ),
			'mocori-insights-visitors'    => __( 'Visitantes', 'mocori-insights' ),
			'mocori-insights-acquisition' => __( 'Aquisição', 'mocori-insights' ),
			'mocori-insights-pages'       => __( 'Páginas', 'mocori-insights' ),
			'mocori-insights-events'      => __( 'Eventos', 'mocori-insights' ),
			'mocori-insights-conversions' => __( 'Conversões', 'mocori-insights' ),
			'mocori-insights-forms'       => __( 'Formulários', 'mocori-insights' ),
			'mocori-insights-campaigns'   => __( 'Campanhas', 'mocori-insights' ),
			'mocori-insights-reports'     => __( 'Relatórios', 'mocori-insights' ),
			'mocori-insights-settings'    => __( 'Configurações', 'mocori-insights' ),
		);

		$first = true;
		foreach ( $pages as $slug => $title ) {
			$page_cap = 'mocori-insights-settings' === $slug ? Mocori_Insights_Capabilities::MANAGE : $cap;
			add_submenu_page(
				'mocori-insights',
				$title,
				$title,
				$page_cap,
				$slug,
				array( $this, 'render' )
			);
			unset( $first );
		}
	}

	/**
	 * Assets.
	 *
	 * @param string $hook Hook.
	 */
	public function assets( $hook ) {
		if ( false === strpos( $hook, 'mocori-insights' ) ) {
			return;
		}
		wp_enqueue_style( 'mocori-insights-admin', MOCORI_INSIGHTS_URL . 'assets/css/admin.css', array(), MOCORI_INSIGHTS_VERSION );
		wp_enqueue_script( 'mocori-insights-admin', MOCORI_INSIGHTS_URL . 'assets/js/admin.js', array(), MOCORI_INSIGHTS_VERSION, true );
		wp_localize_script(
			'mocori-insights-admin',
			'MocoriInsightsAdmin',
			array(
				'realtime' => esc_url_raw( rest_url( 'mocori-insights/v1/realtime' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'accent'   => $this->plugin->settings->get( 'accent_color', '#1f3d34' ),
			)
		);
	}

	/**
	 * Settings POST.
	 */
	public function handle_post() {
		if ( empty( $_POST['mocori_insights_settings_nonce'] ) ) {
			return;
		}
		if ( ! Mocori_Insights_Capabilities::can_manage() ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mocori_insights_settings_nonce'] ) ), 'mocori_insights_settings' ) ) {
			return;
		}

		if ( ! empty( $_POST['mi_conversion_name'] ) ) {
			$engine = new Mocori_Insights_Conversion_Engine( $this->plugin );
			$engine->save_definition(
				array(
					'event_name'      => isset( $_POST['mi_event_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mi_event_name'] ) ) : 'form_submit',
					'match_key'       => isset( $_POST['mi_match_key'] ) ? sanitize_text_field( wp_unslash( $_POST['mi_match_key'] ) ) : 'form_id',
					'match_value'     => isset( $_POST['mi_match_value'] ) ? sanitize_text_field( wp_unslash( $_POST['mi_match_value'] ) ) : '',
					'conversion_name' => sanitize_text_field( wp_unslash( $_POST['mi_conversion_name'] ) ),
					'conversion_type' => isset( $_POST['mi_conversion_type'] ) ? sanitize_text_field( wp_unslash( $_POST['mi_conversion_type'] ) ) : 'lead',
					'is_active'       => 1,
				)
			);
		}

		$keys = array( 'project_name', 'domain', 'timezone', 'currency', 'logo', 'accent_color', 'retention_days', 'consent_mode', 'require_consent', 'cmp_integration', 'tracking_enabled', 'license_key' );
		$pairs = array();
		foreach ( $keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$pairs[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			}
		}
		if ( isset( $pairs['accent_color'] ) && ! preg_match( '/^#[0-9a-fA-F]{6}$/', $pairs['accent_color'] ) ) {
			unset( $pairs['accent_color'] );
		}
		$pairs['require_consent']  = empty( $_POST['require_consent'] ) ? '0' : '1';
		$pairs['cmp_integration']  = empty( $_POST['cmp_integration'] ) ? '0' : '1';
		$pairs['tracking_enabled'] = empty( $_POST['tracking_enabled'] ) ? '0' : '1';
		$pairs['project_configured'] = '1';
		$this->plugin->settings->update( $pairs );

		wp_safe_redirect( admin_url( 'admin.php?page=mocori-insights-settings&updated=1' ) );
		exit;
	}

	/**
	 * Wipe analytics.
	 */
	public function wipe() {
		if ( ! Mocori_Insights_Capabilities::can_manage() ) {
			wp_die( esc_html__( 'Acesso negado.', 'mocori-insights' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'mocori_insights_wipe' );
		( new Mocori_Insights_Retention( $this->plugin ) )->wipe_analytics();
		wp_safe_redirect( admin_url( 'admin.php?page=mocori-insights-settings&wiped=1' ) );
		exit;
	}

	/**
	 * Render current screen.
	 */
	public function render() {
		if ( ! Mocori_Insights_Capabilities::can_view() ) {
			wp_die( esc_html__( 'Acesso negado.', 'mocori-insights' ), '', array( 'response' => 403 ) );
		}

		$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'mocori-insights';
		$preset = isset( $_GET['range'] ) ? sanitize_key( wp_unslash( $_GET['range'] ) ) : '7d';
		$from   = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
		$to     = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
		$range  = Mocori_Insights_Metrics::range( $preset, $from, $to );

		$view = 'dashboard';
		$map  = array(
			'mocori-insights'             => 'dashboard',
			'mocori-insights-visitors'    => 'visitors',
			'mocori-insights-acquisition' => 'acquisition',
			'mocori-insights-pages'       => 'pages',
			'mocori-insights-events'      => 'events',
			'mocori-insights-conversions' => 'conversions',
			'mocori-insights-forms'       => 'forms',
			'mocori-insights-campaigns'   => 'campaigns',
			'mocori-insights-reports'     => 'reports',
			'mocori-insights-settings'    => 'settings',
		);
		if ( isset( $map[ $page ] ) ) {
			$view = $map[ $page ];
		}
		if ( 'settings' === $view && ! Mocori_Insights_Capabilities::can_manage() ) {
			wp_die( esc_html__( 'Acesso negado.', 'mocori-insights' ), '', array( 'response' => 403 ) );
		}

		$metrics   = new Mocori_Insights_Metrics( $this->plugin );
		$reports   = new Mocori_Insights_Reports( $this->plugin );
		$realtime  = new Mocori_Insights_Realtime( $this->plugin );
		$funnels   = new Mocori_Insights_Funnel( $this->plugin );
		$settings  = $this->plugin->settings->all();
		$accent    = $settings['accent_color'];
		$project   = $settings['project_name'];
		$totals     = $metrics->totals( $range );
		$series    = $metrics->series( $range );
		$acq       = $metrics->acquisition( $range );
		$conv_break = $metrics->conversion_breakdown( $range );
		$live      = $realtime->snapshot();
		$export_base = wp_nonce_url( admin_url( 'admin-post.php?action=mocori_insights_export&range=' . rawurlencode( $range['preset'] ) . '&from=' . rawurlencode( $range['from'] ) . '&to=' . rawurlencode( $range['to'] ) ), 'mocori_insights_export' );

		include MOCORI_INSIGHTS_DIR . 'templates/layout.php';
	}

	/**
	 * Format integer.
	 *
	 * @param int $n Number.
	 * @return string
	 */
	public static function n( $n ) {
		return number_format_i18n( (int) $n );
	}
}
