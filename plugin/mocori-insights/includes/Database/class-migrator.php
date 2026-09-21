<?php
/**
 * Schema migrations. Never drop analytics data.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migrator.
 */
class Mocori_Insights_Migrator {
	/**
	 * Upgrade schema in place.
	 */
	public static function maybe_upgrade() {
		$installed = get_option( 'mocori_insights_db_version' );
		if ( $installed === MOCORI_INSIGHTS_DB_VERSION ) {
			return;
		}

		Mocori_Insights_Schema::install();
		update_option( 'mocori_insights_db_version', MOCORI_INSIGHTS_DB_VERSION, false );

		$settings = new Mocori_Insights_Settings();
		$settings->seed_defaults();

		do_action( 'mocori_insights_migrated', $installed, MOCORI_INSIGHTS_DB_VERSION );
	}
}
