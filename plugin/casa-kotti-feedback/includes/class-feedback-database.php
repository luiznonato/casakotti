<?php
/**
 * Tables and fragrance queries.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Database {
	const DB_VERSION = '1.0.0';

	/**
	 * Create tables on activation.
	 */
	public static function activate() {
		self::install_tables();
	}

	/**
	 * Upgrade if stored schema version changed.
	 */
	public static function maybe_upgrade() {
		if ( get_option( 'ckf_db_version' ) !== self::DB_VERSION ) {
			self::install_tables();
		}
	}

	/**
	 * dbDelta install.
	 */
	public static function install_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$feedback = self::feedback_table();
		$frags    = self::fragrance_table();

		$sql_feedback = "CREATE TABLE {$feedback} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL,
			product varchar(40) NOT NULL,
			fragrance varchar(190) NOT NULL,
			fragrance_slug varchar(190) NOT NULL,
			overall_rating tinyint(3) unsigned NOT NULL,
			intensity varchar(40) NOT NULL,
			product_specific_answer varchar(80) NOT NULL DEFAULT '',
			performance varchar(80) NOT NULL,
			presentation_rating tinyint(3) unsigned NOT NULL,
			repurchase_intent varchar(40) NOT NULL,
			nps_score tinyint(3) unsigned NOT NULL,
			improvement_comment text NULL,
			positive_comment text NULL,
			customer_name varchar(190) NOT NULL DEFAULT '',
			customer_email varchar(190) NOT NULL DEFAULT '',
			marketing_consent tinyint(1) unsigned NOT NULL DEFAULT 0,
			source varchar(255) NOT NULL DEFAULT '',
			campaign varchar(190) NOT NULL DEFAULT '',
			product_code varchar(190) NOT NULL DEFAULT '',
			batch varchar(190) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			ip_hash varchar(64) NOT NULL DEFAULT '',
			user_agent varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			KEY product (product),
			KEY fragrance_slug (fragrance_slug),
			KEY created_at (created_at),
			KEY nps_score (nps_score)
		) {$charset};";

		$sql_frags = "CREATE TABLE {$frags} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(190) NOT NULL,
			slug varchar(190) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			sort_order int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY status_order (status, sort_order)
		) {$charset};";

		dbDelta( $sql_feedback );
		dbDelta( $sql_frags );
		update_option( 'ckf_db_version', self::DB_VERSION, false );
	}

	/**
	 * Feedback table name.
	 *
	 * @return string
	 */
	public static function feedback_table() {
		global $wpdb;
		return $wpdb->prefix . 'casa_kotti_feedback';
	}

	/**
	 * Fragrance table name.
	 *
	 * @return string
	 */
	public static function fragrance_table() {
		global $wpdb;
		return $wpdb->prefix . 'casa_kotti_fragrances';
	}

	/**
	 * Active fragrances for the public form.
	 *
	 * @return array<int, object>
	 */
	public static function active_fragrances() {
		global $wpdb;
		$table = self::fragrance_table();
		return $wpdb->get_results( "SELECT id, name, slug FROM {$table} WHERE status = 'active' ORDER BY sort_order ASC, name ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * All fragrances for admin.
	 *
	 * @return array<int, object>
	 */
	public static function all_fragrances() {
		global $wpdb;
		$table = self::fragrance_table();
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY sort_order ASC, name ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Find fragrance by slug.
	 *
	 * @param string $slug Slug.
	 * @return object|null
	 */
	public static function fragrance_by_slug( $slug ) {
		global $wpdb;
		$table = self::fragrance_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s LIMIT 1", $slug ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Insert a feedback row.
	 *
	 * @param array $data Column values.
	 * @return int|false
	 */
	public static function insert_feedback( $data ) {
		global $wpdb;
		$ok = $wpdb->insert( self::feedback_table(), $data );
		return $ok ? (int) $wpdb->insert_id : false;
	}
}
