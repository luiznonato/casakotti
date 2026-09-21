<?php
/**
 * Consent layer. No fingerprinting.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Consent.
 */
class Mocori_Insights_Consent {
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
	 * Whether this payload may be stored.
	 *
	 * @param array $payload Payload.
	 * @return bool
	 */
	public function allows( $payload ) {
		$mode = $this->plugin->settings->get( 'consent_mode', 'cookies' );
		$require = '1' === (string) $this->plugin->settings->get( 'require_consent', '0' ) || 'after_consent' === $mode;

		$granted = false;
		if ( isset( $payload['consent'] ) ) {
			$granted = ( true === $payload['consent'] || 1 === $payload['consent'] || '1' === (string) $payload['consent'] );
		}

		$allowed = apply_filters( 'mocori_insights_consent_allows', null, $payload, $mode );
		if ( is_bool( $allowed ) ) {
			return $allowed;
		}

		if ( $require && ! $granted ) {
			return false;
		}
		return true;
	}

	/**
	 * Cookieless mode.
	 *
	 * @return bool
	 */
	public function cookieless() {
		return 'cookieless' === $this->plugin->settings->get( 'consent_mode', 'cookies' );
	}

	/**
	 * Config for the tracker.
	 *
	 * @return array
	 */
	public function tracker_config() {
		$mode = $this->plugin->settings->get( 'consent_mode', 'cookies' );
		return array(
			'mode'            => $mode,
			'requireConsent'  => ( '1' === (string) $this->plugin->settings->get( 'require_consent', '0' ) || 'after_consent' === $mode ),
			'cmp'             => '1' === (string) $this->plugin->settings->get( 'cmp_integration', '0' ),
			'cookieless'      => 'cookieless' === $mode,
			'cookieVisitor'   => 'mocori_insights_visitor',
			'cookieSession'   => 'mocori_insights_session',
			'sessionMinutes'  => 30,
		);
	}
}
