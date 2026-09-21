<?php
/**
 * Configurable conversions.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conversion engine.
 */
class Mocori_Insights_Conversion_Engine {
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
	 * Match recorded event against conversion definitions.
	 *
	 * @param object $event   Event.
	 * @param object $visitor Visitor.
	 * @param object $session Session.
	 * @return int
	 */
	public function maybe_convert( $event, $visitor, $session ) {
		if ( 'conversion' === $event->event_name ) {
			$meta = is_array( $event->metadata ) ? $event->metadata : array();
			return $this->store(
				$event,
				$visitor,
				$session,
				isset( $meta['conversion_type'] ) ? $meta['conversion_type'] : 'lead',
				isset( $meta['conversion_name'] ) ? $meta['conversion_name'] : 'conversion',
				isset( $meta['reference_id'] ) ? $meta['reference_id'] : '',
				isset( $meta['value'] ) ? $meta['value'] : 0
			);
		}

		foreach ( $this->plugin->settings->conversions() as $def ) {
			if ( $def['event_name'] !== $event->event_name ) {
				continue;
			}
			if ( ! $this->matches( $def, $event ) ) {
				continue;
			}
			$meta = is_array( $event->metadata ) ? $event->metadata : array();
			return $this->store(
				$event,
				$visitor,
				$session,
				$def['conversion_type'],
				$def['conversion_name'],
				isset( $meta['reference_id'] ) ? $meta['reference_id'] : ( isset( $meta['form_id'] ) ? $meta['form_id'] : '' ),
				isset( $meta['value'] ) ? $meta['value'] : 0
			);
		}

		return 0;
	}

	/**
	 * Definition match.
	 *
	 * @param array  $def   Definition.
	 * @param object $event Event.
	 * @return bool
	 */
	private function matches( $def, $event ) {
		$key = isset( $def['match_key'] ) ? $def['match_key'] : '';
		$val = isset( $def['match_value'] ) ? $def['match_value'] : '';
		if ( '' === $key ) {
			return true;
		}
		$meta = is_array( $event->metadata ) ? $event->metadata : array();
		$got  = isset( $meta[ $key ] ) ? (string) $meta[ $key ] : '';
		return $got === (string) $val;
	}

	/**
	 * Persist conversion.
	 *
	 * @param object $event   Event.
	 * @param object $visitor Visitor.
	 * @param object $session Session.
	 * @param string $type    Type.
	 * @param string $name    Name.
	 * @param string $ref     Reference.
	 * @param mixed  $value   Value.
	 * @return int
	 */
	public function store( $event, $visitor, $session, $type, $name, $ref, $value ) {
		$now  = current_time( 'mysql', true );
		$site = $this->plugin->settings->get( 'site_uuid' );
		$touch = Mocori_Insights_Attribution::snapshot( $visitor, $session );
		$id = $this->plugin->repository->insert(
			'conversions',
			array(
				'site_uuid'        => $site,
				'visitor_id'       => (int) $visitor->id,
				'session_id'       => (int) $session->id,
				'event_id'         => isset( $event->id ) ? (int) $event->id : 0,
				'conversion_type'  => substr( sanitize_key( $type ), 0, 64 ),
				'conversion_name'  => substr( sanitize_text_field( $name ), 0, 191 ),
				'reference_id'     => substr( sanitize_text_field( $ref ), 0, 191 ),
				'value'            => is_numeric( $value ) ? $value : 0,
				'currency'         => $this->plugin->settings->get( 'currency', 'BRL' ),
				'first_channel'    => $touch['first'],
				'last_channel'     => $touch['last'],
				'created_at'       => $now,
			),
			array( '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s' )
		);

		if ( $id ) {
			( new Mocori_Insights_Aggregator( $this->plugin ) )->bump( $now, array( 'conversions' => 1 ) );
			$conversion = (object) array(
				'id'               => $id,
				'conversion_type'  => $type,
				'conversion_name'  => $name,
				'reference_id'     => $ref,
			);
			do_action( 'mocori_insights_conversion_recorded', $conversion, $visitor, $session, $event );
		}

		return (int) $id;
	}

	/**
	 * Save a definition.
	 *
	 * @param array $data Data.
	 * @return int
	 */
	public function save_definition( $data ) {
		$now  = current_time( 'mysql', true );
		$site = $this->plugin->settings->get( 'site_uuid' );
		$id   = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
		$row  = array(
			'site_uuid'        => $site,
			'event_name'       => Mocori_Insights_Sanitizer::event_name( $data['event_name'] ),
			'match_key'        => sanitize_key( isset( $data['match_key'] ) ? $data['match_key'] : '' ),
			'match_value'      => sanitize_text_field( isset( $data['match_value'] ) ? $data['match_value'] : '' ),
			'conversion_name'  => sanitize_text_field( $data['conversion_name'] ),
			'conversion_type'  => sanitize_key( isset( $data['conversion_type'] ) ? $data['conversion_type'] : 'lead' ),
			'is_active'        => empty( $data['is_active'] ) ? 0 : 1,
			'updated_at'       => $now,
		);
		if ( $id ) {
			$this->plugin->repository->update( 'conversion_defs', $row, array( 'id' => $id ), array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ), array( '%d' ) );
			return $id;
		}
		$row['created_at'] = $now;
		return $this->plugin->repository->insert( 'conversion_defs', $row, array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ) );
	}
}
