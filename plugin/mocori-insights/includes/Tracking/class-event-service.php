<?php
/**
 * Event persistence.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event service.
 */
class Mocori_Insights_Event_Service {
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
	 * Record an event.
	 *
	 * @param object $visitor Visitor.
	 * @param object $session Session.
	 * @param string $name    Event name.
	 * @param string $page    Page URL.
	 * @param array  $meta    Metadata.
	 * @return int
	 */
	public function record( $visitor, $session, $name, $page, $meta ) {
		$name = Mocori_Insights_Sanitizer::event_name( $name );
		if ( '' === $name || ! $visitor || ! $session ) {
			return 0;
		}

		$now  = current_time( 'mysql', true );
		$site = $this->plugin->settings->get( 'site_uuid' );
		$meta = Mocori_Insights_Sanitizer::metadata( $meta );
		$page = Mocori_Insights_Sanitizer::page_url( $page );

		$id = $this->plugin->repository->insert(
			'events',
			array(
				'site_uuid'   => $site,
				'visitor_id'  => (int) $visitor->id,
				'session_id'  => (int) $session->id,
				'event_name'  => $name,
				'page_url'    => $page,
				'metadata'    => wp_json_encode( $meta ),
				'created_at'  => $now,
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( 'page_view' === $name ) {
			( new Mocori_Insights_Session_Service( $this->plugin ) )->touch( $session, $page, $now, true );
			( new Mocori_Insights_Aggregator( $this->plugin ) )->bump( $now, array( 'pageviews' => 1 ) );
		}

		if ( $id ) {
			$event = (object) array(
				'id'         => $id,
				'event_name' => $name,
				'page_url'   => $page,
				'metadata'   => $meta,
				'created_at' => $now,
			);
			do_action( 'mocori_insights_event_recorded', $event, $visitor, $session );
			( new Mocori_Insights_Conversion_Engine( $this->plugin ) )->maybe_convert( $event, $visitor, $session );
		}

		return (int) $id;
	}
}
