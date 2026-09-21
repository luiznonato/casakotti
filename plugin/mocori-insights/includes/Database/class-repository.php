<?php
/**
 * Low-level persistence helpers.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository.
 */
class Mocori_Insights_Repository {
	/**
	 * Insert a row.
	 *
	 * @param string $logical Table key.
	 * @param array  $data    Data.
	 * @param array  $format  Formats.
	 * @return int
	 */
	public function insert( $logical, $data, $format ) {
		global $wpdb;
		$wpdb->insert( Mocori_Insights_Schema::table( $logical ), $data, $format );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Update rows.
	 *
	 * @param string $logical Table key.
	 * @param array  $data    Data.
	 * @param array  $where   Where.
	 * @param array  $format  Formats.
	 * @param array  $where_format Where formats.
	 * @return int
	 */
	public function update( $logical, $data, $where, $format, $where_format ) {
		global $wpdb;
		return (int) $wpdb->update( Mocori_Insights_Schema::table( $logical ), $data, $where, $format, $where_format );
	}

	/**
	 * Get visitor by UUID.
	 *
	 * @param string $uuid UUID.
	 * @return object|null
	 */
	public function visitor_by_uuid( $uuid ) {
		global $wpdb;
		$table = Mocori_Insights_Schema::table( 'visitors' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE visitor_uuid = %s LIMIT 1", $uuid ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Get session by UUID.
	 *
	 * @param string $uuid UUID.
	 * @return object|null
	 */
	public function session_by_uuid( $uuid ) {
		global $wpdb;
		$table = Mocori_Insights_Schema::table( 'sessions' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE session_uuid = %s LIMIT 1", $uuid ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Latest session for a visitor.
	 *
	 * @param int $visitor_id Visitor id.
	 * @return object|null
	 */
	public function latest_session( $visitor_id ) {
		global $wpdb;
		$table = Mocori_Insights_Schema::table( 'sessions' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE visitor_id = %d ORDER BY last_activity_at DESC LIMIT 1", $visitor_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
