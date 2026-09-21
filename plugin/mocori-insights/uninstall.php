<?php
/**
 * Uninstall. Drops Insights tables and capabilities. Does not touch other plugins.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

require_once __DIR__ . '/includes/autoload.php';

if ( ! defined( 'MOCORI_INSIGHTS_DIR' ) ) {
	define( 'MOCORI_INSIGHTS_DIR', __DIR__ . '/' );
}

$tables = array(
	$wpdb->prefix . 'mocori_insights_settings',
	$wpdb->prefix . 'mocori_insights_visitors',
	$wpdb->prefix . 'mocori_insights_sessions',
	$wpdb->prefix . 'mocori_insights_events',
	$wpdb->prefix . 'mocori_insights_conversions',
	$wpdb->prefix . 'mocori_insights_daily',
	$wpdb->prefix . 'mocori_insights_conversion_defs',
	$wpdb->prefix . 'mocori_insights_identities',
	$wpdb->prefix . 'mocori_insights_funnels',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

delete_option( 'mocori_insights_db_version' );

$role = get_role( 'administrator' );
if ( $role ) {
	$role->remove_cap( 'manage_mocori_insights' );
	$role->remove_cap( 'view_mocori_insights' );
}

wp_clear_scheduled_hook( 'mocori_insights_retention_cron' );
wp_clear_scheduled_hook( 'mocori_insights_aggregate_cron' );
