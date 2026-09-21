<?php
/**
 * Ingest a batch of events.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collector.
 */
class Mocori_Insights_Collector {
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
	 * Ingest payload.
	 *
	 * @param array $payload Payload.
	 * @return array
	 */
	public function ingest( $payload ) {
		if ( '1' !== (string) $this->plugin->settings->get( 'tracking_enabled', '1' ) ) {
			return array( 'ok' => false, 'reason' => 'disabled' );
		}

		$consent = new Mocori_Insights_Consent( $this->plugin );
		if ( ! $consent->allows( $payload ) ) {
			return array( 'ok' => false, 'reason' => 'consent' );
		}

		$events = isset( $payload['events'] ) && is_array( $payload['events'] ) ? $payload['events'] : array();
		if ( ! $events ) {
			return array( 'ok' => true, 'accepted' => 0 );
		}

		$context = $this->context( $payload );
		$visitors = new Mocori_Insights_Visitor_Service( $this->plugin );
		$sessions = new Mocori_Insights_Session_Service( $this->plugin );
		$recorder = new Mocori_Insights_Event_Service( $this->plugin );

		$visitor = $visitors->upsert( isset( $payload['visitor_id'] ) ? $payload['visitor_id'] : '', $context );
		if ( ! $visitor ) {
			return array( 'ok' => false, 'reason' => 'visitor' );
		}

		$resolved = $sessions->resolve( $visitor, isset( $payload['session_id'] ) ? $payload['session_id'] : '', $context );
		$session  = $resolved['session'];
		$accepted = 0;
		$ids      = array();

		if ( $resolved['started'] ) {
			$ids[] = $recorder->record( $visitor, $session, 'session_start', $context['page_url'], array() );
			$accepted++;
		}

		foreach ( array_slice( $events, 0, 25 ) as $event ) {
			$name = isset( $event['name'] ) ? $event['name'] : ( isset( $event['event_name'] ) ? $event['event_name'] : '' );
			$page = isset( $event['page_url'] ) ? $event['page_url'] : $context['page_url'];
			$meta = isset( $event['metadata'] ) ? $event['metadata'] : array();
			$id   = $recorder->record( $visitor, $session, $name, $page, $meta );
			if ( $id ) {
				$accepted++;
				$ids[] = $id;
			}
		}

		return array(
			'ok'          => true,
			'accepted'    => $accepted,
			'visitor_id'  => $visitor->visitor_uuid,
			'session_id'  => $session->session_uuid,
			'event_ids'   => $ids,
		);
	}

	/**
	 * Build context from payload + request.
	 *
	 * @param array $payload Payload.
	 * @return array
	 */
	private function context( $payload ) {
		$page = isset( $payload['page_url'] ) ? Mocori_Insights_Sanitizer::page_url( $payload['page_url'] ) : '/';
		$utm  = isset( $payload['utm'] ) && is_array( $payload['utm'] ) ? $payload['utm'] : array();
		$host = $this->plugin->settings->get( 'domain' );
		if ( ! $host ) {
			$parsed = wp_parse_url( home_url(), PHP_URL_HOST );
			$host   = is_string( $parsed ) ? $parsed : '';
		}
		$attr = Mocori_Insights_Source_Classifier::classify(
			array(
				'utm_source'   => isset( $utm['utm_source'] ) ? $utm['utm_source'] : '',
				'utm_medium'   => isset( $utm['utm_medium'] ) ? $utm['utm_medium'] : '',
				'utm_campaign' => isset( $utm['utm_campaign'] ) ? $utm['utm_campaign'] : '',
				'utm_content'  => isset( $utm['utm_content'] ) ? $utm['utm_content'] : '',
				'utm_term'     => isset( $utm['utm_term'] ) ? $utm['utm_term'] : '',
				'referrer'     => isset( $payload['referrer'] ) ? $payload['referrer'] : '',
				'site_host'    => $host,
			),
			$this->plugin->settings->channel_rules()
		);

		return array(
			'page_url'     => $page,
			'user_agent'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'attribution'  => $attr,
		);
	}
}
