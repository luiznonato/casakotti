<?php
/**
 * First-touch / last-touch snapshot. Complex models are future work.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Attribution.
 */
class Mocori_Insights_Attribution {
	/**
	 * Snapshot first and last channel labels.
	 *
	 * @param object $visitor Visitor.
	 * @param object $session Session.
	 * @return array{first:string,last:string}
	 */
	public static function snapshot( $visitor, $session ) {
		$first = isset( $visitor->first_channel ) ? (string) $visitor->first_channel : '';
		$last  = isset( $session->channel ) ? (string) $session->channel : '';
		if ( '' === $last && isset( $visitor->last_channel ) ) {
			$last = (string) $visitor->last_channel;
		}
		return array(
			'first' => $first ? $first : 'direct',
			'last'  => $last ? $last : 'direct',
		);
	}
}
