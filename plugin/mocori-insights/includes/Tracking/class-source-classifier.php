<?php
/**
 * Acquisition channel classifier.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps UTM + referrer to channels. Rules are configurable per install.
 */
class Mocori_Insights_Source_Classifier {
	/**
	 * Default channel rules. No client names.
	 *
	 * @return array
	 */
	public static function default_rules() {
		return array(
			'paid_search'    => array( 'medium' => array( 'cpc', 'ppc', 'paidsearch' ) ),
			'paid_social'    => array( 'medium' => array( 'paidsocial', 'paid_social', 'cpc_social' ) ),
			'email'          => array( 'medium' => array( 'email', 'e-mail' ) ),
			'whatsapp'       => array( 'source' => array( 'whatsapp', 'wa' ), 'medium' => array( 'whatsapp', 'wa' ) ),
			'organic_social' => array(
				'hosts'  => array( 'instagram.com', 'facebook.com', 'fb.com', 'l.instagram.com', 't.co', 'twitter.com', 'x.com', 'linkedin.com', 'tiktok.com', 'pinterest.com', 'youtube.com' ),
				'source' => array( 'instagram', 'facebook', 'meta', 'twitter', 'x', 'linkedin', 'tiktok', 'pinterest', 'youtube', 'social' ),
			),
			'organic_search' => array(
				'hosts'  => array( 'google.', 'bing.com', 'yahoo.', 'duckduckgo.com', 'baidu.com', 'ecosia.org' ),
				'medium' => array( 'organic' ),
			),
			'referral'       => array(),
			'direct'         => array(),
			'other'          => array(),
		);
	}

	/**
	 * Classify a hit.
	 *
	 * @param array $input utm_*, referrer, landing, site_host.
	 * @param array $rules Optional rules.
	 * @return array{source:string,medium:string,campaign:string,content:string,term:string,channel:string,referrer:string}
	 */
	public static function classify( $input, $rules = array() ) {
		$utm_source   = self::norm( isset( $input['utm_source'] ) ? $input['utm_source'] : '' );
		$utm_medium   = self::norm( isset( $input['utm_medium'] ) ? $input['utm_medium'] : '' );
		$utm_campaign = self::clip( isset( $input['utm_campaign'] ) ? $input['utm_campaign'] : '' );
		$utm_content  = self::clip( isset( $input['utm_content'] ) ? $input['utm_content'] : '' );
		$utm_term     = self::clip( isset( $input['utm_term'] ) ? $input['utm_term'] : '' );
		$referrer     = isset( $input['referrer'] ) ? trim( (string) $input['referrer'] ) : '';
		$site_host    = self::norm( isset( $input['site_host'] ) ? $input['site_host'] : '' );
		$ref_host     = self::host( $referrer );
		$rules        = $rules ? $rules : self::default_rules();

		$source  = $utm_source;
		$medium  = $utm_medium;
		$channel = 'other';

		if ( $utm_source || $utm_medium || $utm_campaign ) {
			$channel = self::match_utm( $utm_source, $utm_medium, $rules );
			if ( ! $source ) {
				$source = $utm_medium ? $utm_medium : 'campaign';
			}
			if ( ! $medium ) {
				$medium = $utm_source ? 'referral' : 'campaign';
			}
		} elseif ( $ref_host && $site_host && false !== strpos( $ref_host, $site_host ) ) {
			$channel = 'direct';
			$source  = 'direct';
			$medium  = 'none';
			$referrer = '';
		} elseif ( $ref_host ) {
			$channel = self::match_host( $ref_host, $rules );
			$source  = $source ? $source : self::source_from_host( $ref_host );
			if ( 'organic_search' === $channel ) {
				$medium = $medium ? $medium : 'organic';
			} elseif ( 'organic_social' === $channel ) {
				$medium = $medium ? $medium : 'social';
			} else {
				$channel = 'referral';
				$medium  = $medium ? $medium : 'referral';
			}
		} else {
			$channel = 'direct';
			$source  = 'direct';
			$medium  = 'none';
		}

		$labels = array(
			'direct'         => 'Direct',
			'organic_search' => 'Organic Search',
			'paid_search'    => 'Paid Search',
			'organic_social' => 'Organic Social',
			'paid_social'    => 'Paid Social',
			'referral'       => 'Referral',
			'email'          => 'Email',
			'whatsapp'       => 'WhatsApp',
			'other'          => 'Other',
		);

		return array(
			'source'   => $source,
			'medium'   => $medium,
			'campaign' => $utm_campaign,
			'content'  => $utm_content,
			'term'     => $utm_term,
			'channel'  => $channel,
			'label'    => isset( $labels[ $channel ] ) ? $labels[ $channel ] : 'Other',
			'referrer' => $referrer,
		);
	}

	/**
	 * Normalize.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function norm( $value ) {
		return strtolower( trim( (string) $value ) );
	}

	/**
	 * Clip display value.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function clip( $value ) {
		return substr( trim( (string) $value ), 0, 191 );
	}

	/**
	 * Host from URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private static function host( $url ) {
		if ( '' === $url ) {
			return '';
		}
		$host = wp_parse_url( $url, PHP_URL_HOST );
		return $host ? strtolower( $host ) : '';
	}

	/**
	 * Match UTM.
	 *
	 * @param string $source Source.
	 * @param string $medium Medium.
	 * @param array  $rules  Rules.
	 * @return string
	 */
	private static function match_utm( $source, $medium, $rules ) {
		$order = array( 'whatsapp', 'email', 'paid_search', 'paid_social', 'organic_social', 'organic_search' );
		foreach ( $order as $channel ) {
			$rule = isset( $rules[ $channel ] ) ? $rules[ $channel ] : array();
			if ( ! empty( $rule['medium'] ) && in_array( $medium, $rule['medium'], true ) ) {
				return $channel;
			}
			if ( ! empty( $rule['source'] ) && in_array( $source, $rule['source'], true ) ) {
				return $channel;
			}
		}
		if ( in_array( $medium, array( 'cpc', 'ppc', 'paid', 'paidsearch' ), true ) ) {
			return 'paid_search';
		}
		if ( in_array( $medium, array( 'social', 'organic_social' ), true ) ) {
			return 'organic_social';
		}
		if ( 'organic' === $medium ) {
			return 'organic_search';
		}
		if ( 'referral' === $medium ) {
			return 'referral';
		}
		return 'other';
	}

	/**
	 * Match referrer host.
	 *
	 * @param string $host  Host.
	 * @param array  $rules Rules.
	 * @return string
	 */
	private static function match_host( $host, $rules ) {
		foreach ( array( 'organic_social', 'organic_search', 'whatsapp' ) as $channel ) {
			$needles = isset( $rules[ $channel ]['hosts'] ) ? $rules[ $channel ]['hosts'] : array();
			foreach ( $needles as $needle ) {
				if ( false !== strpos( $host, $needle ) ) {
					return $channel;
				}
			}
		}
		return 'referral';
	}

	/**
	 * Friendly source from host.
	 *
	 * @param string $host Host.
	 * @return string
	 */
	private static function source_from_host( $host ) {
		$host = preg_replace( '/^www\./', '', $host );
		$parts = explode( '.', $host );
		return $parts ? $parts[0] : $host;
	}
}
