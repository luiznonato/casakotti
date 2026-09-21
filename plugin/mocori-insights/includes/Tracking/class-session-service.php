<?php
/**
 * Sessions. 30 minutes of inactivity.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Session service.
 */
class Mocori_Insights_Session_Service {
	const IDLE_SECONDS = 1800;

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
	 * Resolve session, rotating after idle timeout.
	 *
	 * @param object $visitor Visitor.
	 * @param string $session_uuid Proposed UUID.
	 * @param array  $context Context.
	 * @return array{session:object,started:bool}
	 */
	public function resolve( $visitor, $session_uuid, $context ) {
		$now     = current_time( 'mysql', true );
		$now_ts  = strtotime( $now . ' UTC' );
		$site    = $this->plugin->settings->get( 'site_uuid' );
		$uuid    = Mocori_Insights_Sanitizer::uuid( $session_uuid );
		$session = $uuid ? $this->plugin->repository->session_by_uuid( $uuid ) : null;
		$attr    = isset( $context['attribution'] ) ? $context['attribution'] : array();
		$page    = isset( $context['page_url'] ) ? $context['page_url'] : '/';

		if ( $session && (int) $session->visitor_id === (int) $visitor->id ) {
			$last = strtotime( $session->last_activity_at . ' UTC' );
			if ( $last && ( $now_ts - $last ) <= self::IDLE_SECONDS ) {
				$this->touch( $session, $page, $now, false );
				return array(
					'session' => $this->plugin->repository->session_by_uuid( $session->session_uuid ),
					'started' => false,
				);
			}
		}

		$latest = $this->plugin->repository->latest_session( (int) $visitor->id );
		if ( $latest ) {
			$last = strtotime( $latest->last_activity_at . ' UTC' );
			if ( $last && ( $now_ts - $last ) <= self::IDLE_SECONDS ) {
				$this->touch( $latest, $page, $now, false );
				return array(
					'session' => $this->plugin->repository->session_by_uuid( $latest->session_uuid ),
					'started' => false,
				);
			}
		}

		$new_uuid = Mocori_Insights_Settings::uuid();
		$this->plugin->repository->insert(
			'sessions',
			array(
				'site_uuid'         => $site,
				'session_uuid'      => $new_uuid,
				'visitor_id'        => (int) $visitor->id,
				'started_at'        => $now,
				'last_activity_at'  => $now,
				'source'            => isset( $attr['source'] ) ? $attr['source'] : '',
				'medium'            => isset( $attr['medium'] ) ? $attr['medium'] : '',
				'campaign'          => isset( $attr['campaign'] ) ? $attr['campaign'] : '',
				'content'           => isset( $attr['content'] ) ? $attr['content'] : '',
				'term'              => isset( $attr['term'] ) ? $attr['term'] : '',
				'channel'           => isset( $attr['channel'] ) ? $attr['channel'] : '',
				'referrer'          => isset( $attr['referrer'] ) ? substr( (string) $attr['referrer'], 0, 500 ) : '',
				'landing_page'      => $page,
				'exit_page'         => $page,
				'pageviews'         => 0,
				'created_at'        => $now,
				'updated_at'        => $now,
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		$session = $this->plugin->repository->session_by_uuid( $new_uuid );
		do_action( 'mocori_insights_session_started', $session, $visitor, $context );
		( new Mocori_Insights_Aggregator( $this->plugin ) )->bump( $now, array( 'sessions' => 1 ) );

		$is_returning = strtotime( $visitor->first_seen . ' UTC' ) < ( $now_ts - 60 );
		if ( $is_returning ) {
			( new Mocori_Insights_Aggregator( $this->plugin ) )->bump( $now, array( 'returning_visitors' => 1 ) );
		}

		return array(
			'session' => $session,
			'started' => true,
		);
	}

	/**
	 * Touch session.
	 *
	 * @param object $session Session.
	 * @param string $page    Page.
	 * @param string $now     Now.
	 * @param bool   $pageview Increment pageviews.
	 */
	public function touch( $session, $page, $now, $pageview ) {
		$data = array(
			'last_activity_at' => $now,
			'exit_page'        => $page,
			'updated_at'       => $now,
		);
		$format = array( '%s', '%s', '%s' );
		if ( $pageview ) {
			$data['pageviews'] = (int) $session->pageviews + 1;
			$format[]          = '%d';
		}
		$this->plugin->repository->update( 'sessions', $data, array( 'id' => (int) $session->id ), $format, array( '%d' ) );
	}
}
