<?php
/**
 * Activation / deactivation.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installer.
 */
class Mocori_Insights_Install {
	/**
	 * Activate plugin.
	 */
	public static function activate() {
		Mocori_Insights_Schema::install();
		Mocori_Insights_Capabilities::grant_administrator();

		$settings = new Mocori_Insights_Settings();
		$settings->seed_defaults();

		if ( ! wp_next_scheduled( 'mocori_insights_retention_cron' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mocori_insights_retention_cron' );
		}
		if ( ! wp_next_scheduled( 'mocori_insights_aggregate_cron' ) ) {
			wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'hourly', 'mocori_insights_aggregate_cron' );
		}

		update_option( 'mocori_insights_db_version', MOCORI_INSIGHTS_DB_VERSION, false );
		flush_rewrite_rules( false );

		do_action( 'mocori_insights_activated' );
	}

	/**
	 * Deactivate plugin. Data is kept.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'mocori_insights_retention_cron' );
		wp_clear_scheduled_hook( 'mocori_insights_aggregate_cron' );
		flush_rewrite_rules( false );
		do_action( 'mocori_insights_deactivated' );
	}
}
