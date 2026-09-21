<?php
/**
 * Plugin orchestrator.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin container.
 */
class Mocori_Insights_Plugin {
	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Settings store.
	 *
	 * @var Mocori_Insights_Settings
	 */
	public $settings;

	/**
	 * Repository.
	 *
	 * @var Mocori_Insights_Repository
	 */
	public $repository;

	/**
	 * Public API facade.
	 *
	 * @var Mocori_Insights_Public_API
	 */
	public $api;

	/**
	 * Instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}
		return self::$instance;
	}

	/**
	 * Wire services and WordPress hooks.
	 */
	private function boot() {
		$this->settings   = new Mocori_Insights_Settings();
		$this->repository = new Mocori_Insights_Repository();
		$this->api        = new Mocori_Insights_Public_API( $this );

		Mocori_Insights_Migrator::maybe_upgrade();
		Mocori_Insights_Capabilities::register();

		( new Mocori_Insights_Frontend( $this ) )->hooks();
		( new Mocori_Insights_Rest( $this ) )->hooks();
		( new Mocori_Insights_Admin( $this ) )->hooks();
		( new Mocori_Insights_Retention( $this ) )->hooks();
		( new Mocori_Insights_Eraser( $this ) )->hooks();
		( new Mocori_Insights_Aggregator( $this ) )->hooks();
		( new Mocori_Insights_Integrations( $this ) )->hooks();
		( new Mocori_Insights_Exporter( $this ) )->hooks();

		do_action( 'mocori_insights_loaded', $this );
	}

	/**
	 * Track an event from PHP.
	 *
	 * @param string $event_name Event name.
	 * @param array  $payload    Event payload.
	 * @return int Event id or 0.
	 */
	public function track( $event_name, $payload = array() ) {
		return $this->api->track( $event_name, $payload );
	}

	/**
	 * Identify a lead against the current/known visitor.
	 *
	 * @param array $identity Identity payload.
	 * @return bool
	 */
	public function identify( $identity ) {
		return $this->api->identify( $identity );
	}

	/**
	 * Record a conversion.
	 *
	 * @param string $event_name Event that converted.
	 * @param array  $payload    Conversion payload.
	 * @return int Conversion id or 0.
	 */
	public function convert( $event_name, $payload = array() ) {
		return $this->api->convert( $event_name, $payload );
	}
}
