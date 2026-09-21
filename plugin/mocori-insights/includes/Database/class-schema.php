<?php
/**
 * Database schema.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Table names and CREATE statements.
 */
class Mocori_Insights_Schema {
	/**
	 * Logical table suffix map.
	 *
	 * @return array
	 */
	public static function tables() {
		return array(
			'settings'        => 'mocori_insights_settings',
			'visitors'        => 'mocori_insights_visitors',
			'sessions'        => 'mocori_insights_sessions',
			'events'          => 'mocori_insights_events',
			'conversions'     => 'mocori_insights_conversions',
			'daily'           => 'mocori_insights_daily',
			'conversion_defs' => 'mocori_insights_conversion_defs',
			'identities'      => 'mocori_insights_identities',
			'funnels'         => 'mocori_insights_funnels',
		);
	}

	/**
	 * Prefixed table name.
	 *
	 * @param string $logical Logical key.
	 * @return string
	 */
	public static function table( $logical ) {
		global $wpdb;
		$tables = self::tables();
		$suffix = isset( $tables[ $logical ] ) ? $tables[ $logical ] : 'mocori_insights_' . sanitize_key( $logical );
		return $wpdb->prefix . $suffix;
	}

	/**
	 * Create or update tables without dropping data.
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		foreach ( self::statements( $charset ) as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * CREATE TABLE statements.
	 *
	 * @param string $charset Charset collate.
	 * @return array
	 */
	public static function statements( $charset ) {
		$settings        = self::table( 'settings' );
		$visitors        = self::table( 'visitors' );
		$sessions        = self::table( 'sessions' );
		$events          = self::table( 'events' );
		$conversions     = self::table( 'conversions' );
		$daily           = self::table( 'daily' );
		$conversion_defs = self::table( 'conversion_defs' );
		$identities      = self::table( 'identities' );
		$funnels         = self::table( 'funnels' );

		return array(
			"CREATE TABLE {$settings} (
				setting_key varchar(191) NOT NULL,
				setting_value longtext NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (setting_key)
			) {$charset};",
			"CREATE TABLE {$visitors} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				site_uuid char(36) NOT NULL,
				visitor_uuid char(36) NOT NULL,
				first_seen datetime NOT NULL,
				last_seen datetime NOT NULL,
				first_source varchar(64) NOT NULL DEFAULT '',
				first_medium varchar(64) NOT NULL DEFAULT '',
				first_campaign varchar(191) NOT NULL DEFAULT '',
				first_content varchar(191) NOT NULL DEFAULT '',
				first_term varchar(191) NOT NULL DEFAULT '',
				first_channel varchar(64) NOT NULL DEFAULT '',
				first_landing_page varchar(500) NOT NULL DEFAULT '',
				last_source varchar(64) NOT NULL DEFAULT '',
				last_medium varchar(64) NOT NULL DEFAULT '',
				last_campaign varchar(191) NOT NULL DEFAULT '',
				last_channel varchar(64) NOT NULL DEFAULT '',
				device_type varchar(32) NOT NULL DEFAULT '',
				os varchar(64) NOT NULL DEFAULT '',
				browser varchar(64) NOT NULL DEFAULT '',
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY visitor_uuid (visitor_uuid),
				KEY site_last_seen (site_uuid, last_seen),
				KEY first_seen (first_seen)
			) {$charset};",
			"CREATE TABLE {$sessions} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				site_uuid char(36) NOT NULL,
				session_uuid char(36) NOT NULL,
				visitor_id bigint(20) unsigned NOT NULL,
				started_at datetime NOT NULL,
				last_activity_at datetime NOT NULL,
				source varchar(64) NOT NULL DEFAULT '',
				medium varchar(64) NOT NULL DEFAULT '',
				campaign varchar(191) NOT NULL DEFAULT '',
				content varchar(191) NOT NULL DEFAULT '',
				term varchar(191) NOT NULL DEFAULT '',
				channel varchar(64) NOT NULL DEFAULT '',
				referrer varchar(500) NOT NULL DEFAULT '',
				landing_page varchar(500) NOT NULL DEFAULT '',
				exit_page varchar(500) NOT NULL DEFAULT '',
				pageviews int(10) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY session_uuid (session_uuid),
				KEY visitor_activity (visitor_id, last_activity_at),
				KEY site_started (site_uuid, started_at),
				KEY campaign (campaign)
			) {$charset};",
			"CREATE TABLE {$events} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				site_uuid char(36) NOT NULL,
				visitor_id bigint(20) unsigned NOT NULL,
				session_id bigint(20) unsigned NOT NULL,
				event_name varchar(64) NOT NULL,
				page_url varchar(500) NOT NULL DEFAULT '',
				metadata longtext NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY lookup_event (site_uuid, event_name, created_at),
				KEY visitor_created (visitor_id, created_at),
				KEY session_created (session_id, created_at),
				KEY page_created (page_url(191), created_at)
			) {$charset};",
			"CREATE TABLE {$conversions} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				site_uuid char(36) NOT NULL,
				visitor_id bigint(20) unsigned NOT NULL,
				session_id bigint(20) unsigned NOT NULL,
				event_id bigint(20) unsigned NOT NULL DEFAULT 0,
				conversion_type varchar(64) NOT NULL DEFAULT '',
				conversion_name varchar(191) NOT NULL DEFAULT '',
				reference_id varchar(191) NOT NULL DEFAULT '',
				value decimal(18,4) NOT NULL DEFAULT 0,
				currency varchar(8) NOT NULL DEFAULT '',
				first_channel varchar(64) NOT NULL DEFAULT '',
				last_channel varchar(64) NOT NULL DEFAULT '',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY site_created (site_uuid, created_at),
				KEY visitor (visitor_id),
				KEY conversion_type (conversion_type, created_at)
			) {$charset};",
			"CREATE TABLE {$daily} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				site_uuid char(36) NOT NULL,
				stat_date date NOT NULL,
				visitors int(10) unsigned NOT NULL DEFAULT 0,
				new_visitors int(10) unsigned NOT NULL DEFAULT 0,
				returning_visitors int(10) unsigned NOT NULL DEFAULT 0,
				sessions int(10) unsigned NOT NULL DEFAULT 0,
				pageviews int(10) unsigned NOT NULL DEFAULT 0,
				conversions int(10) unsigned NOT NULL DEFAULT 0,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY site_date (site_uuid, stat_date)
			) {$charset};",
			"CREATE TABLE {$conversion_defs} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				site_uuid char(36) NOT NULL,
				event_name varchar(64) NOT NULL,
				match_key varchar(64) NOT NULL DEFAULT '',
				match_value varchar(191) NOT NULL DEFAULT '',
				conversion_name varchar(191) NOT NULL,
				conversion_type varchar(64) NOT NULL DEFAULT 'lead',
				is_active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY site_event (site_uuid, event_name)
			) {$charset};",
			"CREATE TABLE {$identities} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				site_uuid char(36) NOT NULL,
				visitor_id bigint(20) unsigned NOT NULL,
				session_id bigint(20) unsigned NOT NULL DEFAULT 0,
				lead_id varchar(191) NOT NULL,
				lead_source varchar(64) NOT NULL DEFAULT '',
				identified_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY visitor (visitor_id),
				KEY lead (lead_source, lead_id)
			) {$charset};",
			"CREATE TABLE {$funnels} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				site_uuid char(36) NOT NULL,
				funnel_name varchar(191) NOT NULL,
				steps longtext NOT NULL,
				is_active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY site_active (site_uuid, is_active)
			) {$charset};",
		);
	}
}
