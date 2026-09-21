<?php
/**
 * Retention cron. Drops detailed events, keeps aggregates.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retention.
 */
class Mocori_Insights_Retention {
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
		add_action( 'mocori_insights_retention_cron', array( $this, 'purge' ) );
	}

	/**
	 * Purge detailed rows older than retention_days.
	 */
	public function purge() {
		$days = absint( $this->plugin->settings->get( 'retention_days', 365 ) );
		if ( $days < 1 ) {
			return;
		}
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
		foreach ( array( 'events', 'sessions', 'conversions', 'identities' ) as $logical ) {
			$table = Mocori_Insights_Schema::table( $logical );
			$col   = 'identities' === $logical ? 'identified_at' : ( 'sessions' === $logical ? 'started_at' : 'created_at' );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$col} < %s", $cutoff ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$visitors = Mocori_Insights_Schema::table( 'visitors' );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$visitors} WHERE last_seen < %s", $cutoff ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		do_action( 'mocori_insights_retention_purged', $cutoff, $days );
	}

	/**
	 * Wipe all analytics (keep settings and definitions).
	 */
	public function wipe_analytics() {
		if ( ! Mocori_Insights_Capabilities::can_manage() ) {
			return false;
		}
		global $wpdb;
		foreach ( array( 'events', 'sessions', 'conversions', 'identities', 'visitors', 'daily' ) as $logical ) {
			$table = Mocori_Insights_Schema::table( $logical );
			$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		do_action( 'mocori_insights_analytics_wiped' );
		return true;
	}
}
