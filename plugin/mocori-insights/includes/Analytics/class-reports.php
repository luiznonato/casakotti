<?php
/**
 * Report queries.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reports.
 */
class Mocori_Insights_Reports {
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
	 * Date bounds.
	 *
	 * @param array $range Range.
	 * @return array
	 */
	private function bounds( $range ) {
		return array( $range['from'] . ' 00:00:00', $range['to'] . ' 23:59:59' );
	}

	/**
	 * Visitors list.
	 *
	 * @param array $range Range.
	 * @param int   $limit Limit.
	 * @return array
	 */
	public function visitors( $range, $limit = 100 ) {
		global $wpdb;
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$table = Mocori_Insights_Schema::table( 'visitors' );
		list( $start, $end ) = $this->bounds( $range );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT visitor_uuid, first_seen, last_seen, first_channel, last_channel, first_landing_page, device_type, os, browser FROM {$table} WHERE site_uuid = %s AND last_seen BETWEEN %s AND %s ORDER BY last_seen DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$start,
				$end,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Sessions list.
	 *
	 * @param array $range Range.
	 * @param int   $limit Limit.
	 * @return array
	 */
	public function sessions( $range, $limit = 100 ) {
		global $wpdb;
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$table = Mocori_Insights_Schema::table( 'sessions' );
		list( $start, $end ) = $this->bounds( $range );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT session_uuid, visitor_id, started_at, last_activity_at, source, medium, campaign, channel, landing_page, exit_page, pageviews FROM {$table} WHERE site_uuid = %s AND started_at BETWEEN %s AND %s ORDER BY started_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$start,
				$end,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Events list.
	 *
	 * @param array $range Range.
	 * @param int   $limit Limit.
	 * @return array
	 */
	public function events( $range, $limit = 200 ) {
		global $wpdb;
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$table = Mocori_Insights_Schema::table( 'events' );
		list( $start, $end ) = $this->bounds( $range );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_name, page_url, metadata, created_at, visitor_id, session_id FROM {$table} WHERE site_uuid = %s AND created_at BETWEEN %s AND %s ORDER BY created_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$start,
				$end,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Conversions list.
	 *
	 * @param array $range Range.
	 * @param int   $limit Limit.
	 * @return array
	 */
	public function conversions( $range, $limit = 200 ) {
		global $wpdb;
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$table = Mocori_Insights_Schema::table( 'conversions' );
		list( $start, $end ) = $this->bounds( $range );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT conversion_name, conversion_type, reference_id, value, currency, first_channel, last_channel, created_at, visitor_id FROM {$table} WHERE site_uuid = %s AND created_at BETWEEN %s AND %s ORDER BY created_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$start,
				$end,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Campaign report.
	 *
	 * @param array $range  Range.
	 * @param array $filters Filters.
	 * @return array
	 */
	public function campaigns( $range, $filters = array() ) {
		global $wpdb;
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$sess  = Mocori_Insights_Schema::table( 'sessions' );
		$conv  = Mocori_Insights_Schema::table( 'conversions' );
		list( $start, $end ) = $this->bounds( $range );

		$where  = array( 's.site_uuid = %s', 's.started_at BETWEEN %s AND %s' );
		$args   = array( $site, $start, $end );
		foreach ( array( 'campaign' => 's.campaign', 'source' => 's.source', 'medium' => 's.medium' ) as $key => $col ) {
			if ( ! empty( $filters[ $key ] ) ) {
				$where[] = $col . ' = %s';
				$args[]  = sanitize_text_field( $filters[ $key ] );
			}
		}
		$sql = "SELECT s.campaign, s.source, s.medium,
			COUNT(DISTINCT s.visitor_id) AS visitors,
			COUNT(DISTINCT s.id) AS sessions,
			COUNT(DISTINCT c.id) AS conversions
			FROM {$sess} s
			LEFT JOIN {$conv} c ON c.session_id = s.id
			WHERE " . implode( ' AND ', $where ) . '
			GROUP BY s.campaign, s.source, s.medium
			ORDER BY sessions DESC LIMIT 100';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$out = array();
		foreach ( $rows as $row ) {
			$sessions = (int) $row['sessions'];
			$convs    = (int) $row['conversions'];
			$out[]    = array(
				'campaign'         => $row['campaign'],
				'source'           => $row['source'],
				'medium'           => $row['medium'],
				'visitors'         => (int) $row['visitors'],
				'sessions'         => $sessions,
				'conversions'      => $convs,
				'conversion_rate'  => $sessions ? round( ( $convs / $sessions ) * 100, 1 ) : 0,
			);
		}
		return $out;
	}

	/**
	 * Pages report.
	 *
	 * @param array $range Range.
	 * @return array
	 */
	public function pages( $range ) {
		global $wpdb;
		$site   = $this->plugin->settings->get( 'site_uuid' );
		$events = Mocori_Insights_Schema::table( 'events' );
		$sess   = Mocori_Insights_Schema::table( 'sessions' );
		$conv   = Mocori_Insights_Schema::table( 'conversions' );
		list( $start, $end ) = $this->bounds( $range );

		$pages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT page_url,
					COUNT(*) AS pageviews,
					COUNT(DISTINCT visitor_id) AS visitors
				FROM {$events}
				WHERE site_uuid = %s AND event_name = %s AND created_at BETWEEN %s AND %s
				GROUP BY page_url
				ORDER BY pageviews DESC
				LIMIT 100", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				'page_view',
				$start,
				$end
			),
			ARRAY_A
		);

		$landings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT landing_page AS page_url, COUNT(*) AS entries FROM {$sess} WHERE site_uuid = %s AND started_at BETWEEN %s AND %s GROUP BY landing_page", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$start,
				$end
			),
			ARRAY_A
		);
		$exits = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT exit_page AS page_url, COUNT(*) AS exits FROM {$sess} WHERE site_uuid = %s AND started_at BETWEEN %s AND %s GROUP BY exit_page", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$start,
				$end
			),
			ARRAY_A
		);
		$convs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.page_url, COUNT(*) AS conversions FROM {$conv} c INNER JOIN {$events} e ON e.id = c.event_id WHERE c.site_uuid = %s AND c.created_at BETWEEN %s AND %s GROUP BY e.page_url", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$start,
				$end
			),
			ARRAY_A
		);

		$map = static function ( $rows, $key ) {
			$out = array();
			foreach ( (array) $rows as $row ) {
				$out[ $row['page_url'] ] = (int) $row[ $key ];
			}
			return $out;
		};
		$L = $map( $landings, 'entries' );
		$X = $map( $exits, 'exits' );
		$C = $map( $convs, 'conversions' );

		$out = array();
		foreach ( (array) $pages as $row ) {
			$url   = $row['page_url'];
			$out[] = array(
				'page_url'    => $url,
				'pageviews'   => (int) $row['pageviews'],
				'visitors'    => (int) $row['visitors'],
				'entries'     => isset( $L[ $url ] ) ? $L[ $url ] : 0,
				'exits'       => isset( $X[ $url ] ) ? $X[ $url ] : 0,
				'conversions' => isset( $C[ $url ] ) ? $C[ $url ] : 0,
			);
		}
		return $out;
	}

	/**
	 * Forms report from form_* events.
	 *
	 * @param array $range Range.
	 * @return array
	 */
	public function forms( $range ) {
		global $wpdb;
		$site  = $this->plugin->settings->get( 'site_uuid' );
		$table = Mocori_Insights_Schema::table( 'events' );
		list( $start, $end ) = $this->bounds( $range );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_name, metadata, page_url FROM {$table} WHERE site_uuid = %s AND event_name IN ('form_view','form_start','form_submit') AND created_at BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site,
				$start,
				$end
			),
			ARRAY_A
		);
		$out = array();
		foreach ( (array) $rows as $row ) {
			$meta = json_decode( $row['metadata'], true );
			$form = is_array( $meta ) && ! empty( $meta['form_id'] ) ? $meta['form_id'] : '(unknown)';
			if ( ! isset( $out[ $form ] ) ) {
				$out[ $form ] = array(
					'form_id'   => $form,
					'form_name' => is_array( $meta ) && ! empty( $meta['form_name'] ) ? $meta['form_name'] : $form,
					'views'     => 0,
					'starts'    => 0,
					'submits'   => 0,
				);
			}
			if ( 'form_view' === $row['event_name'] ) {
				$out[ $form ]['views']++;
			} elseif ( 'form_start' === $row['event_name'] ) {
				$out[ $form ]['starts']++;
			} else {
				$out[ $form ]['submits']++;
			}
		}
		return array_values( $out );
	}
}
