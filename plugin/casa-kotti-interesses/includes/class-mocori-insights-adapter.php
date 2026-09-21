<?php
/**
 * Optional adapter: Casa Kotti — Interesses → Mocori Insights.
 *
 * Lives in the client plugin. Insights does not depend on this file.
 *
 * @package Casa_Kotti_Interesses
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seed project + conversion only when Insights is present.
 *
 * @param array $defaults Defaults.
 * @return array
 */
function cki_mocori_insights_default_settings( $defaults ) {
	if ( ! is_array( $defaults ) ) {
		return $defaults;
	}
	if ( empty( $defaults['project_configured'] ) || '0' === (string) $defaults['project_configured'] ) {
		$defaults['project_name'] = 'Casa Kotti';
		$defaults['domain']       = 'casakotti.com.br';
	}
	return $defaults;
}
add_filter( 'mocori_insights_default_settings', 'cki_mocori_insights_default_settings' );

/**
 * Register the launch interest form as a generic conversion definition.
 *
 * @param array $defs Definitions.
 * @return array
 */
function cki_mocori_insights_seed_conversions( $defs ) {
	$defs   = is_array( $defs ) ? $defs : array();
	$defs[] = array(
		'event_name'      => 'form_submit',
		'match_key'       => 'form_id',
		'match_value'     => 'interest-launch',
		'conversion_name' => 'Interesse no lançamento',
		'conversion_type' => 'lead',
	);
	return $defs;
}
add_filter( 'mocori_insights_seed_conversions', 'cki_mocori_insights_seed_conversions' );

/**
 * Apply first-install project labels if Insights is active and still unconfigured.
 *
 * @param Mocori_Insights_Plugin $plugin Plugin.
 */
function cki_mocori_insights_configure_project( $plugin ) {
	if ( ! is_object( $plugin ) || empty( $plugin->settings ) ) {
		return;
	}
	if ( '1' === (string) $plugin->settings->get( 'project_configured', '0' ) ) {
		return;
	}
	$plugin->settings->update(
		array(
			'project_name'       => 'Casa Kotti',
			'domain'             => 'casakotti.com.br',
			'project_configured' => '1',
		)
	);
}
add_action( 'mocori_insights_loaded', 'cki_mocori_insights_configure_project' );

/**
 * After a lead is stored, relate visitor → lead_id without sending the email.
 *
 * @param int    $lead_id Lead id.
 * @param string $email   Email (not forwarded to Insights).
 */
function cki_mocori_insights_after_lead( $lead_id, $email = '' ) {
	unset( $email );
	if ( ! function_exists( 'mocori_insights' ) || ! $lead_id ) {
		return;
	}

	$visitor = isset( $_COOKIE['mocori_insights_visitor'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['mocori_insights_visitor'] ) ) : '';
	$session = isset( $_COOKIE['mocori_insights_session'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['mocori_insights_session'] ) ) : '';

	mocori_insights()->identify(
		array(
			'visitor_id' => $visitor,
			'session_id' => $session,
			'lead_id'    => (string) absint( $lead_id ),
			'source'     => 'custom_form',
		)
	);

	mocori_insights()->track(
		'form_submit',
		array(
			'visitor_id' => $visitor,
			'session_id' => $session,
			'page_url'   => wp_get_referer() ? wp_get_referer() : '/',
			'metadata'   => array(
				'form_id'      => 'interest-launch',
				'form_name'    => 'interest-launch',
				'reference_id' => (string) absint( $lead_id ),
			),
		)
	);
}
add_action( 'cki_interest_registered', 'cki_mocori_insights_after_lead', 10, 2 );
