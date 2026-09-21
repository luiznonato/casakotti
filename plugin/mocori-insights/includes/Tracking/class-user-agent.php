<?php
/**
 * Coarse user-agent parsing. Not a fingerprint.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Device / OS / browser labels.
 */
class Mocori_Insights_User_Agent {
	/**
	 * Parse UA into coarse labels.
	 *
	 * @param string $ua User-Agent.
	 * @return array{device_type:string,os:string,browser:string}
	 */
	public static function parse( $ua ) {
		$ua = (string) $ua;
		$device = 'desktop';
		if ( preg_match( '/iPad|Tablet|PlayBook/i', $ua ) ) {
			$device = 'tablet';
		} elseif ( preg_match( '/Mobi|iPhone|Android.+Mobile|iPod/i', $ua ) ) {
			$device = 'mobile';
		} elseif ( preg_match( '/Android/i', $ua ) ) {
			$device = 'tablet';
		}

		$os = '';
		if ( preg_match( '/iPhone|iPad|iPod/i', $ua ) ) {
			$os = 'iOS';
		} elseif ( preg_match( '/Android/i', $ua ) ) {
			$os = 'Android';
		} elseif ( preg_match( '/Mac OS X/i', $ua ) ) {
			$os = 'macOS';
		} elseif ( preg_match( '/Windows/i', $ua ) ) {
			$os = 'Windows';
		} elseif ( preg_match( '/Linux/i', $ua ) ) {
			$os = 'Linux';
		}

		$browser = '';
		if ( preg_match( '/Edg\//i', $ua ) || preg_match( '/EdgiOS/i', $ua ) ) {
			$browser = 'Edge';
		} elseif ( preg_match( '/OPR\/|Opera/i', $ua ) ) {
			$browser = 'Opera';
		} elseif ( preg_match( '/CriOS|Chrome\//i', $ua ) ) {
			$browser = 'Chrome';
		} elseif ( preg_match( '/FxiOS|Firefox\//i', $ua ) ) {
			$browser = 'Firefox';
		} elseif ( preg_match( '/Safari\//i', $ua ) ) {
			$browser = 'Safari';
		}

		return array(
			'device_type' => $device,
			'os'          => $os,
			'browser'     => $browser,
		);
	}
}
