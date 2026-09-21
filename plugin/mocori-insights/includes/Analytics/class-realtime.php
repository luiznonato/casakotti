<?php
/**
 * Active visitors in the last five minutes.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Realtime.
 */
class Mocori_Insights_Realtime {
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
	 * Snapshot.
	 *
	 * @return array
	 */
	public function snapshot() {
		global $wpdb;
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$table = Mocori_Insights_Schema::table( 'events' );
		$since = gmdate( 'Y-m-d H:i:s', time() - 5 * MINUTE_IN_SECONDS );
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT visitor_id) FROM {$table} WHERE site_uuid = %s AND created_at >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$since
			)
		);
		$pages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT page_url, COUNT(DISTINCT visitor_id) AS visitors FROM {$table} WHERE site_uuid = %s AND created_at >= %s GROUP BY page_url ORDER BY visitors DESC LIMIT 12", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$since
			),
			ARRAY_A
		);
		return array(
			'active' => $total,
			'pages'  => is_array( $pages ) ? $pages : array(),
		);
	}
}
