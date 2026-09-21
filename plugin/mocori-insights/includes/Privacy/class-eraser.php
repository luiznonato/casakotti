<?php
/**
 * WordPress privacy exporters / erasers. Behavioral data only.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Eraser.
 */
class Mocori_Insights_Eraser {
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
		add_action( 'admin_init', array( $this, 'policy' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	/**
	 * Suggested policy text. No invented legal claims.
	 */
	public function policy() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content(
				__( 'Mocori Insights', 'mocori-insights' ),
				wp_kses_post( '<p>' . __( 'Este site pode usar o plugin Mocori Insights para medir visitas, sessões, origens de tráfego e eventos de formulário de forma agregada. Os identificadores de visitante são aleatórios. O endereço IP completo não é armazenado. Ajuste este texto à política de privacidade aprovada para o site.', 'mocori-insights' ) . '</p>' )
			);
		}
	}

	/**
	 * Register eraser.
	 *
	 * @param array $erasers Erasers.
	 * @return array
	 */
	public function register_eraser( $erasers ) {
		$erasers['mocori-insights'] = array(
			'eraser_friendly_name' => __( 'Mocori Insights', 'mocori-insights' ),
			'callback'             => array( $this, 'erase_by_email' ),
		);
		return $erasers;
	}

	/**
	 * Insights does not store email. Client plugins may map lead_id.
	 *
	 * @param string $email Email.
	 * @param int    $page  Page.
	 * @return array
	 */
	public function erase_by_email( $email, $page = 1 ) {
		unset( $email );
		if ( $page > 1 ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}
		do_action( 'mocori_insights_erase_requested', $email = '' );
		return array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array( __( 'Mocori Insights não armazena e-mail. A exclusão de um visitante específico usa o visitor_id no painel do plugin.', 'mocori-insights' ) ),
			'done'           => true,
		);
	}

	/**
	 * Delete one visitor UUID and related rows.
	 *
	 * @param string $uuid UUID.
	 * @return bool
	 */
	public function erase_visitor( $uuid ) {
		$uuid = Mocori_Insights_Sanitizer::uuid( $uuid );
		if ( ! $uuid || ! Mocori_Insights_Capabilities::can_manage() ) {
			return false;
		}
		$visitor = $this->plugin->repository->visitor_by_uuid( $uuid );
		if ( ! $visitor ) {
			return false;
		}
		global $wpdb;
		$id = (int) $visitor->id;
		foreach ( array( 'events' => 'visitor_id', 'sessions' => 'visitor_id', 'conversions' => 'visitor_id', 'identities' => 'visitor_id' ) as $logical => $col ) {
			$table = Mocori_Insights_Schema::table( $logical );
			$wpdb->delete( $table, array( $col => $id ), array( '%d' ) );
		}
		$wpdb->delete( Mocori_Insights_Schema::table( 'visitors' ), array( 'id' => $id ), array( '%d' ) );
		return true;
	}
}
