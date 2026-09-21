<?php
/**
 * Visitors.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Visitor service.
 */
class Mocori_Insights_Visitor_Service {
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
	 * Find or create a visitor.
	 *
	 * @param string $uuid    Visitor UUID.
	 * @param array  $context Context.
	 * @return object|null
	 */
	public function upsert( $uuid, $context ) {
		$uuid = Mocori_Insights_Sanitizer::uuid( $uuid );
		if ( '' === $uuid ) {
			$uuid = Mocori_Insights_Settings::uuid();
		}

		$existing = $this->plugin->repository->visitor_by_uuid( $uuid );
		$now      = current_time( 'mysql', true );
		$site     = $this->plugin->settings->get( 'site_uuid' );
		$ua       = Mocori_Insights_User_Agent::parse( isset( $context['user_agent'] ) ? $context['user_agent'] : '' );
		$attr     = isset( $context['attribution'] ) ? $context['attribution'] : array();

		if ( $existing ) {
			$update = array(
				'last_seen'     => $now,
				'updated_at'    => $now,
				'device_type'   => $ua['device_type'],
				'os'            => $ua['os'],
				'browser'       => $ua['browser'],
				'last_source'   => isset( $attr['source'] ) ? $attr['source'] : $existing->last_source,
				'last_medium'   => isset( $attr['medium'] ) ? $attr['medium'] : $existing->last_medium,
				'last_campaign' => isset( $attr['campaign'] ) ? $attr['campaign'] : $existing->last_campaign,
				'last_channel'  => isset( $attr['channel'] ) ? $attr['channel'] : $existing->last_channel,
			);
			$this->plugin->repository->update( 'visitors', $update, array( 'id' => (int) $existing->id ), array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ), array( '%d' ) );
			return $this->plugin->repository->visitor_by_uuid( $uuid );
		}

		$id = $this->plugin->repository->insert(
			'visitors',
			array(
				'site_uuid'          => $site,
				'visitor_uuid'       => $uuid,
				'first_seen'         => $now,
				'last_seen'          => $now,
				'first_source'       => isset( $attr['source'] ) ? $attr['source'] : '',
				'first_medium'       => isset( $attr['medium'] ) ? $attr['medium'] : '',
				'first_campaign'     => isset( $attr['campaign'] ) ? $attr['campaign'] : '',
				'first_content'      => isset( $attr['content'] ) ? $attr['content'] : '',
				'first_term'         => isset( $attr['term'] ) ? $attr['term'] : '',
				'first_channel'      => isset( $attr['channel'] ) ? $attr['channel'] : '',
				'first_landing_page' => isset( $context['page_url'] ) ? $context['page_url'] : '/',
				'last_source'        => isset( $attr['source'] ) ? $attr['source'] : '',
				'last_medium'        => isset( $attr['medium'] ) ? $attr['medium'] : '',
				'last_campaign'      => isset( $attr['campaign'] ) ? $attr['campaign'] : '',
				'last_channel'       => isset( $attr['channel'] ) ? $attr['channel'] : '',
				'device_type'        => $ua['device_type'],
				'os'                 => $ua['os'],
				'browser'            => $ua['browser'],
				'created_at'         => $now,
				'updated_at'         => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$visitor = $this->plugin->repository->visitor_by_uuid( $uuid );
		if ( $visitor ) {
			do_action( 'mocori_insights_visitor_created', $visitor, $context );
			( new Mocori_Insights_Aggregator( $this->plugin ) )->bump( $now, array( 'visitors' => 1, 'new_visitors' => 1 ) );
		}
		unset( $id );
		return $visitor;
	}
}
