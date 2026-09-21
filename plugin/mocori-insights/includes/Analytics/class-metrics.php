<?php
/**
 * Dashboard metrics from the daily table, with live fallback.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Metrics.
 */
class Mocori_Insights_Metrics {
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
	 * Range from request preset.
	 *
	 * @param string $preset Preset.
	 * @param string $from   Custom from.
	 * @param string $to     Custom to.
	 * @return array{from:string,to:string,preset:string}
	 */
	public static function range( $preset, $from = '', $to = '' ) {
		$today = current_time( 'Y-m-d' );
		$preset = $preset ? $preset : '7d';
		switch ( $preset ) {
			case 'today':
				return array( 'from' => $today, 'to' => $today, 'preset' => 'today' );
			case 'yesterday':
				$y = gmdate( 'Y-m-d', strtotime( $today . ' -1 day' ) );
				return array( 'from' => $y, 'to' => $y, 'preset' => 'yesterday' );
			case '30d':
				return array( 'from' => gmdate( 'Y-m-d', strtotime( $today . ' -29 days' ) ), 'to' => $today, 'preset' => '30d' );
			case '90d':
				return array( 'from' => gmdate( 'Y-m-d', strtotime( $today . ' -89 days' ) ), 'to' => $today, 'preset' => '90d' );
			case 'custom':
				$from = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ? $from : $today;
				$to   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ? $to : $today;
				return array( 'from' => $from, 'to' => $to, 'preset' => 'custom' );
			case '7d':
			default:
				return array( 'from' => gmdate( 'Y-m-d', strtotime( $today . ' -6 days' ) ), 'to' => $today, 'preset' => '7d' );
		}
	}

	/**
	 * Totals for a range.
	 *
	 * @param array $range Range.
	 * @return array
	 */
	public function totals( $range ) {
		global $wpdb;
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$table = Mocori_Insights_Schema::table( 'daily' );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(visitors),0) AS visitors, COALESCE(SUM(new_visitors),0) AS new_visitors, COALESCE(SUM(returning_visitors),0) AS returning_visitors, COALESCE(SUM(sessions),0) AS sessions, COALESCE(SUM(pageviews),0) AS pageviews, COALESCE(SUM(conversions),0) AS conversions FROM {$table} WHERE site_uuid = %s AND stat_date BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$range['from'],
				$range['to']
			),
			ARRAY_A
		);
		$visitors    = (int) $row['visitors'];
		$sessions    = (int) $row['sessions'];
		$pageviews   = (int) $row['pageviews'];
		$conversions = (int) $row['conversions'];
		$rate        = $sessions > 0 ? round( ( $conversions / $sessions ) * 100, 1 ) : 0;

		return array(
			'visitors'            => $visitors,
			'sessions'            => $sessions,
			'pageviews'           => $pageviews,
			'conversions'         => $conversions,
			'conversion_rate'     => $rate,
			'new_visitors'        => (int) $row['new_visitors'],
			'returning_visitors'  => (int) $row['returning_visitors'],
		);
	}

	/**
	 * Time series.
	 *
	 * @param array $range Range.
	 * @return array
	 */
	public function series( $range ) {
		global $wpdb;
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$table = Mocori_Insights_Schema::table( 'daily' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT stat_date, visitors, sessions, pageviews, conversions FROM {$table} WHERE site_uuid = %s AND stat_date BETWEEN %s AND %s ORDER BY stat_date ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$range['from'],
				$range['to']
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Acquisition mix.
	 *
	 * @param array $range Range.
	 * @return array
	 */
	public function acquisition( $range ) {
		global $wpdb;
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$table = Mocori_Insights_Schema::table( 'sessions' );
		$start = $range['from'] . ' 00:00:00';
		$end   = $range['to'] . ' 23:59:59';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT channel, COUNT(*) AS sessions, COUNT(DISTINCT visitor_id) AS visitors FROM {$table} WHERE site_uuid = %s AND started_at BETWEEN %s AND %s GROUP BY channel ORDER BY sessions DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$start,
				$end
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Conversion breakdown.
	 *
	 * @param array $range Range.
	 * @return array
	 */
	public function conversion_breakdown( $range ) {
		global $wpdb;
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$table = Mocori_Insights_Schema::table( 'conversions' );
		$start = $range['from'] . ' 00:00:00';
		$end   = $range['to'] . ' 23:59:59';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT conversion_name, conversion_type, COUNT(*) AS total FROM {$table} WHERE site_uuid = %s AND created_at BETWEEN %s AND %s GROUP BY conversion_name, conversion_type ORDER BY total DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$start,
				$end
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}
}
