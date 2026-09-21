<?php
/**
 * PHP facade used by other plugins. Insights stays standalone.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public API.
 */
class Mocori_Insights_Public_API {
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
	 * Track.
	 *
	 * @param string $event_name Event.
	 * @param array  $payload    Payload.
	 * @return int
	 */
	public function track( $event_name, $payload = array() ) {
		$payload = is_array( $payload ) ? $payload : array();
		$payload['events'] = array(
			array(
				'name'     => $event_name,
				'page_url' => isset( $payload['page_url'] ) ? $payload['page_url'] : ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/' ),
				'metadata' => isset( $payload['metadata'] ) ? $payload['metadata'] : $payload,
			),
		);
		$result = ( new Mocori_Insights_Collector( $this->plugin ) )->ingest( $payload );
		return empty( $result['event_ids'] ) ? 0 : (int) end( $result['event_ids'] );
	}

	/**
	 * Identify a lead without storing PII.
	 *
	 * @param array $identity Identity.
	 * @return bool
	 */
	public function identify( $identity ) {
		$visitor_uuid = isset( $identity['visitor_id'] ) ? Mocori_Insights_Sanitizer::uuid( $identity['visitor_id'] ) : '';
		$session_uuid = isset( $identity['session_id'] ) ? Mocori_Insights_Sanitizer::uuid( $identity['session_id'] ) : '';
		$lead_id      = isset( $identity['lead_id'] ) ? sanitize_text_field( (string) $identity['lead_id'] ) : '';
		$source       = isset( $identity['source'] ) ? sanitize_key( $identity['source'] ) : 'custom';
		if ( '' === $lead_id ) {
			return false;
		}

		$visitor = $visitor_uuid ? $this->plugin->repository->visitor_by_uuid( $visitor_uuid ) : null;
		$session = $session_uuid ? $this->plugin->repository->session_by_uuid( $session_uuid ) : null;
		if ( ! $visitor ) {
			return false;
		}

		$this->plugin->repository->insert(
			'identities',
			array(
				'site_uuid'      => $this->plugin->settings->get( 'site_uuid' ),
				'visitor_id'     => (int) $visitor->id,
				'session_id'     => $session ? (int) $session->id : 0,
				'lead_id'        => substr( $lead_id, 0, 191 ),
				'lead_source'    => substr( $source, 0, 64 ),
				'identified_at'  => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s' )
		);

		do_action( 'mocori_insights_visitor_identified', $visitor, $lead_id, $source, $identity );
		return true;
	}

	/**
	 * Convert.
	 *
	 * @param string $event_name Event.
	 * @param array  $payload    Payload.
	 * @return int
	 */
	public function convert( $event_name, $payload = array() ) {
		$payload = is_array( $payload ) ? $payload : array();
		$meta    = isset( $payload['metadata'] ) && is_array( $payload['metadata'] ) ? $payload['metadata'] : $payload;
		$meta['conversion_name'] = isset( $payload['conversion_name'] ) ? $payload['conversion_name'] : ( isset( $meta['conversion_name'] ) ? $meta['conversion_name'] : $event_name );
		$meta['conversion_type'] = isset( $payload['conversion_type'] ) ? $payload['conversion_type'] : ( isset( $meta['conversion_type'] ) ? $meta['conversion_type'] : 'lead' );
		return $this->track( $event_name, array_merge( $payload, array( 'metadata' => $meta ) ) );
	}

	/**
	 * Consent helper for PHP.
	 *
	 * @param bool $granted Granted.
	 */
	public function set_consent( $granted ) {
		do_action( 'mocori_insights_consent_changed', (bool) $granted );
	}
}

if ( ! function_exists( 'mocori_insights_track' ) ) {
	/**
	 * Procedural track helper.
	 *
	 * @param string $event Event.
	 * @param array  $payload Payload.
	 * @return int
	 */
	function mocori_insights_track( $event, $payload = array() ) {
		return mocori_insights()->track( $event, $payload );
	}
}
