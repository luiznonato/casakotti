<?php
/**
 * REST collect + admin JSON.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST.
 */
class Mocori_Insights_Rest {
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
		add_action( 'rest_api_init', array( $this, 'register' ) );
	}

	/**
	 * Routes.
	 */
	public function register() {
		register_rest_route(
			'mocori-insights/v1',
			'/collect',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'collect' ),
				'permission_callback' => array( $this, 'collect_permission' ),
			)
		);
		register_rest_route(
			'mocori-insights/v1',
			'/realtime',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'realtime' ),
				'permission_callback' => array( $this, 'view_permission' ),
			)
		);
	}

	/**
	 * Public collect with token + rate limit. IP is hashed and not stored.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function collect_permission( $request ) {
		$token = (string) $request->get_header( 'X-Mocori-Insights-Token' );
		if ( '' === $token ) {
			$params = $request->get_json_params();
			$token  = isset( $params['token'] ) ? (string) $params['token'] : '';
		}
		$expected = (string) $this->plugin->settings->get( 'collect_token' );
		if ( ! $expected || ! hash_equals( $expected, $token ) ) {
			return new WP_Error( 'mocori_insights_token', __( 'Token inválido.', 'mocori-insights' ), array( 'status' => 403 ) );
		}
		if ( ! $this->rate_limit() ) {
			return new WP_Error( 'mocori_insights_rate', __( 'Limite excedido.', 'mocori-insights' ), array( 'status' => 429 ) );
		}
		return true;
	}

	/**
	 * View permission.
	 *
	 * @return bool
	 */
	public function view_permission() {
		return Mocori_Insights_Capabilities::can_view();
	}

	/**
	 * Collect.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function collect( $request ) {
		$payload = $request->get_json_params();
		if ( ! is_array( $payload ) ) {
			$payload = array();
		}
		$result = ( new Mocori_Insights_Collector( $this->plugin ) )->ingest( $payload );
		return rest_ensure_response( $result );
	}

	/**
	 * Realtime.
	 *
	 * @return WP_REST_Response
	 */
	public function realtime() {
		return rest_ensure_response( ( new Mocori_Insights_Realtime( $this->plugin ) )->snapshot() );
	}

	/**
	 * Rate limit using hashed IP transient. IP is not persisted.
	 *
	 * @return bool
	 */
	private function rate_limit() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'mi_rl_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
		$num = (int) get_transient( $key );
		if ( $num >= 120 ) {
			return false;
		}
		set_transient( $key, $num + 1, MINUTE_IN_SECONDS );
		return true;
	}
}
