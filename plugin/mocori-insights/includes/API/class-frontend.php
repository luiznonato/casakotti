<?php
/**
 * Public tracker enqueue.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend.
 */
class Mocori_Insights_Frontend {
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue tracker.
	 */
	public function enqueue() {
		if ( is_admin() || '1' !== (string) $this->plugin->settings->get( 'tracking_enabled', '1' ) ) {
			return;
		}

		wp_enqueue_script(
			'mocori-insights-tracker',
			MOCORI_INSIGHTS_URL . 'assets/js/tracker.js',
			array(),
			MOCORI_INSIGHTS_VERSION,
			true
		);

		$consent = new Mocori_Insights_Consent( $this->plugin );
		wp_localize_script(
			'mocori-insights-tracker',
			'MocoriInsightsConfig',
			array(
				'endpoint' => esc_url_raw( rest_url( 'mocori-insights/v1/collect' ) ),
				'token'    => $this->plugin->settings->get( 'collect_token' ),
				'consent'  => $consent->tracker_config(),
			)
		);
	}
}
