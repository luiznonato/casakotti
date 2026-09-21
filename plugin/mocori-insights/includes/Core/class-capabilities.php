<?php
/**
 * Capabilities.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capability helpers.
 */
class Mocori_Insights_Capabilities {
	const MANAGE = 'manage_mocori_insights';
	const VIEW   = 'view_mocori_insights';

	/**
	 * Register caps on the administrator role.
	 */
	public static function register() {
		add_filter( 'user_has_cap', array( __CLASS__, 'map_admin_caps' ), 10, 4 );
	}

	/**
	 * Grant caps on activation.
	 */
	public static function grant_administrator() {
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( self::MANAGE );
			$role->add_cap( self::VIEW );
		}
	}

	/**
	 * Remove caps on uninstall.
	 */
	public static function revoke() {
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->remove_cap( self::MANAGE );
			$role->remove_cap( self::VIEW );
		}
	}

	/**
	 * Map manage_options administrators to Insights caps without role === administrator checks in UI.
	 *
	 * @param array   $allcaps All caps.
	 * @param array   $caps    Requested.
	 * @param array   $args    Args.
	 * @param WP_User $user    User.
	 * @return array
	 */
	public static function map_admin_caps( $allcaps, $caps, $args, $user ) {
		unset( $caps, $args );
		if ( empty( $user->ID ) ) {
			return $allcaps;
		}
		if ( ! empty( $allcaps['manage_options'] ) ) {
			$allcaps[ self::MANAGE ] = true;
			$allcaps[ self::VIEW ]   = true;
		}
		if ( ! empty( $allcaps[ self::MANAGE ] ) ) {
			$allcaps[ self::VIEW ] = true;
		}
		return $allcaps;
	}

	/**
	 * Can view reports.
	 *
	 * @return bool
	 */
	public static function can_view() {
		return current_user_can( self::VIEW );
	}

	/**
	 * Can manage settings.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return current_user_can( self::MANAGE );
	}
}
