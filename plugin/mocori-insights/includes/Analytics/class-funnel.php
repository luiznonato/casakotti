<?php
/**
 * Generic funnel architecture.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Funnel.
 */
class Mocori_Insights_Funnel {
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
	 * All funnels.
	 *
	 * @return array
	 */
	public function all() {
		global $wpdb;
		$table = Mocori_Insights_Schema::table( 'funnels' );
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE site_uuid = %s ORDER BY id ASC", $site ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Compute counts per step for a funnel definition.
	 *
	 * @param array $funnel Funnel row.
	 * @param array $range  Range.
	 * @return array
	 */
	public function compute( $funnel, $range ) {
		$steps = json_decode( $funnel['steps'], true );
		if ( ! is_array( $steps ) ) {
			return array();
		}
		global $wpdb;
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$table = Mocori_Insights_Schema::table( 'events' );
		$start = $range['from'] . ' 00:00:00';
		$end   = $range['to'] . ' 23:59:59';
		$out   = array();
		foreach ( $steps as $step ) {
			$name = isset( $step['event_name'] ) ? $step['event_name'] : '';
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT visitor_id) FROM {$table} WHERE site_uuid = %s AND event_name = %s AND created_at BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$site,
					$name,
					$start,
					$end
				)
			);
			$out[] = array(
				'label'      => isset( $step['label'] ) ? $step['label'] : $name,
				'event_name' => $name,
				'visitors'   => $count,
			);
		}
		return $out;
	}
}
