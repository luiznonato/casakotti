<?php
/**
 * Daily aggregate counters.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggregator.
 */
class Mocori_Insights_Aggregator {
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
		add_action( 'mocori_insights_aggregate_cron', array( $this, 'rebuild_yesterday' ) );
	}

	/**
	 * Increment today's counters.
	 *
	 * @param string $datetime UTC datetime.
	 * @param array  $deltas   Deltas.
	 */
	public function bump( $datetime, $deltas ) {
		global $wpdb;
		$site = $this->plugin->settings->get( 'site_uuid' );
		$date = gmdate( 'Y-m-d', strtotime( $datetime . ' UTC' ) );
		$table = Mocori_Insights_Schema::table( 'daily' );
		$now   = current_time( 'mysql', true );

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE site_uuid = %s AND stat_date = %s", $site, $date ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $exists ) {
			$wpdb->insert(
				$table,
				array(
					'site_uuid'           => $site,
					'stat_date'           => $date,
					'visitors'            => 0,
					'new_visitors'        => 0,
					'returning_visitors'  => 0,
					'sessions'            => 0,
					'pageviews'           => 0,
					'conversions'         => 0,
					'updated_at'          => $now,
				),
				array( '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s' )
			);
		}

		$set = array();
		foreach ( array( 'visitors', 'new_visitors', 'returning_visitors', 'sessions', 'pageviews', 'conversions' ) as $col ) {
			if ( ! empty( $deltas[ $col ] ) ) {
				$set[] = $col . ' = ' . $col . ' + ' . absint( $deltas[ $col ] );
			}
		}
		if ( ! $set ) {
			return;
		}
		$set[] = $wpdb->prepare( 'updated_at = %s', $now );
		$sql   = "UPDATE {$table} SET " . implode( ', ', $set ) . ' WHERE site_uuid = %s AND stat_date = %s';
		$wpdb->query( $wpdb->prepare( $sql, $site, $date ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Rebuild a date from events (idempotent repair).
	 */
	public function rebuild_yesterday() {
		$date = gmdate( 'Y-m-d', time() - DAY_IN_SECONDS );
		$this->rebuild_date( $date );
	}

	/**
	 * Rebuild one date.
	 *
	 * @param string $date Y-m-d.
	 */
	public function rebuild_date( $date ) {
		global $wpdb;
		$site     = $this->plugin->settings->get( 'site_uuid' );
		$visitors = Mocori_Insights_Schema::table( 'visitors' );
		$sessions = Mocori_Insights_Schema::table( 'sessions' );
		$events   = Mocori_Insights_Schema::table( 'events' );
		$convs    = Mocori_Insights_Schema::table( 'conversions' );
		$daily    = Mocori_Insights_Schema::table( 'daily' );
		$start    = $date . ' 00:00:00';
		$end      = $date . ' 23:59:59';

		$new_visitors = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$visitors} WHERE site_uuid = %s AND first_seen BETWEEN %s AND %s", $site, $start, $end ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$session_c    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$sessions} WHERE site_uuid = %s AND started_at BETWEEN %s AND %s", $site, $start, $end ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$visitor_c    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT visitor_id) FROM {$sessions} WHERE site_uuid = %s AND started_at BETWEEN %s AND %s", $site, $start, $end ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$pageviews    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$events} WHERE site_uuid = %s AND event_name = %s AND created_at BETWEEN %s AND %s", $site, 'page_view', $start, $end ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$conversions  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$convs} WHERE site_uuid = %s AND created_at BETWEEN %s AND %s", $site, $start, $end ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$returning    = max( 0, $visitor_c - $new_visitors );

		$wpdb->replace(
			$daily,
			array(
				'site_uuid'           => $site,
				'stat_date'           => $date,
				'visitors'            => $visitor_c,
				'new_visitors'        => $new_visitors,
				'returning_visitors'  => $returning,
				'sessions'            => $session_c,
				'pageviews'           => $pageviews,
				'conversions'         => $conversions,
				'updated_at'          => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s' )
		);
	}
}
