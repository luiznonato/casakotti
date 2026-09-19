<?php
/**
 * Sanitization, rate limit, hashed IP.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Security {
	/**
	 * Irreversible visitor identifier for spam control.
	 *
	 * @return string
	 */
	public static function ip_hash() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) );
	}

	/**
	 * Rolling submission limit without storing a raw IP.
	 *
	 * @param int $max Max attempts per hour.
	 * @return bool
	 */
	public static function rate_limit_allows( $max = 8 ) {
		$key = 'ckf_rate_' . self::ip_hash();
		$num = (int) get_transient( $key );
		if ( $num >= $max ) {
			return false;
		}
		set_transient( $key, $num + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Map a URL product token to a known product slug.
	 *
	 * @param string $raw Raw query value.
	 * @return string Empty when unknown.
	 */
	public static function sanitize_product_param( $raw ) {
		$raw = sanitize_title( (string) $raw );
		$map = array(
			'home-spray'  => 'home-spray',
			'homespray'   => 'home-spray',
			'home_spray'  => 'home-spray',
			'spray'       => 'home-spray',
			'difusor'     => 'difusor',
			'diffuser'    => 'difusor',
			'automotivo'  => 'automotivo',
			'auto'        => 'automotivo',
			'refil'       => 'refil',
			'refill'      => 'refil',
			'mais-de-um'  => 'mais-de-um',
			'varios'      => 'mais-de-um',
		);
		return isset( $map[ $raw ] ) ? $map[ $raw ] : '';
	}

	/**
	 * Sanitize a campaign/source/batch token.
	 *
	 * @param string $raw Raw value.
	 * @return string
	 */
	public static function sanitize_token( $raw ) {
		$value = sanitize_text_field( (string) $raw );
		return substr( $value, 0, 190 );
	}

	/**
	 * Neutralize CSV formula injection.
	 *
	 * @param mixed $value Cell value.
	 * @return string
	 */
	public static function csv_safe( $value ) {
		$value = (string) $value;
		if ( preg_match( '/^[\s]*[=+\-@]/u', $value ) ) {
			return "'" . $value;
		}
		return $value;
	}

	/**
	 * Truncate user agent.
	 *
	 * @return string
	 */
	public static function user_agent() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return substr( $ua, 0, 255 );
	}
}
