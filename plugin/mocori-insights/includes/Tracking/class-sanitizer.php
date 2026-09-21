<?php
/**
 * Strip PII and bound metadata.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitizer.
 */
class Mocori_Insights_Sanitizer {
	/**
	 * Keys that must never be stored.
	 *
	 * @return array
	 */
	public static function blocked_keys() {
		return apply_filters(
			'mocori_insights_blocked_metadata_keys',
			array( 'email', 'e-mail', 'mail', 'phone', 'tel', 'name', 'first_name', 'last_name', 'full_name', 'cpf', 'cnpj', 'address', 'password', 'pass', 'token', 'card', 'ip', 'user_ip' )
		);
	}

	/**
	 * Sanitize metadata JSON-able array.
	 *
	 * @param mixed $metadata Raw metadata.
	 * @return array
	 */
	public static function metadata( $metadata ) {
		if ( is_string( $metadata ) ) {
			$decoded = json_decode( $metadata, true );
			$metadata = is_array( $decoded ) ? $decoded : array();
		}
		if ( ! is_array( $metadata ) ) {
			return array();
		}
		$clean = self::walk( $metadata, 0 );
		$json  = wp_json_encode( $clean );
		if ( strlen( (string) $json ) > 4000 ) {
			$clean = array( 'truncated' => 1 );
		}
		return $clean;
	}

	/**
	 * Recurse with depth limit.
	 *
	 * @param array $data  Data.
	 * @param int   $depth Depth.
	 * @return array
	 */
	private static function walk( $data, $depth ) {
		if ( $depth > 3 ) {
			return array();
		}
		$blocked = self::blocked_keys();
		$out     = array();
		$i       = 0;
		foreach ( $data as $key => $value ) {
			if ( $i++ > 40 ) {
				break;
			}
			$norm = strtolower( preg_replace( '/[^a-z0-9_]/i', '', (string) $key ) );
			if ( in_array( $norm, $blocked, true ) ) {
				continue;
			}
			$safe_key = substr( sanitize_key( (string) $key ), 0, 64 );
			if ( '' === $safe_key ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$out[ $safe_key ] = self::walk( $value, $depth + 1 );
			} elseif ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
				$out[ $safe_key ] = $value;
			} else {
				$out[ $safe_key ] = substr( sanitize_text_field( (string) $value ), 0, 300 );
			}
		}
		return $out;
	}

	/**
	 * Validate UUID.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function uuid( $value ) {
		$value = strtolower( trim( (string) $value ) );
		if ( preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $value ) ) {
			return $value;
		}
		return '';
	}

	/**
	 * Page path/url, same-origin relative preferred.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function page_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '/';
		}
		$parts = wp_parse_url( $url );
		if ( empty( $parts ) ) {
			return '/';
		}
		$path  = isset( $parts['path'] ) ? $parts['path'] : '/';
		$query = isset( $parts['query'] ) ? '?' . $parts['query'] : '';
		$path  = $path ? $path : '/';
		return substr( $path . $query, 0, 500 );
	}

	/**
	 * Allowed native + reserved future event names, plus custom via filter.
	 *
	 * @param string $name Name.
	 * @return string
	 */
	public static function event_name( $name ) {
		$name = strtolower( sanitize_key( (string) $name ) );
		$allow = apply_filters(
			'mocori_insights_allowed_events',
			array(
				'page_view',
				'session_start',
				'form_view',
				'form_start',
				'form_submit',
				'conversion',
				'outbound_click',
				'product_view',
				'add_to_cart',
				'checkout_start',
				'purchase',
				'lead_created',
				'appointment_created',
				'download',
				'video_play',
			)
		);
		if ( in_array( $name, $allow, true ) ) {
			return $name;
		}
		if ( strlen( $name ) >= 3 && strlen( $name ) <= 64 ) {
			return $name;
		}
		return '';
	}
}
