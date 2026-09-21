<?php
/**
 * Installation settings. Not used for raw analytics.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Key/value settings stored in the dedicated settings table.
 */
class Mocori_Insights_Settings {
	/**
	 * Runtime cache.
	 *
	 * @var array|null
	 */
	private $cache = null;

	/**
	 * Default values. Client-agnostic.
	 *
	 * @return array
	 */
	public static function defaults() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$defaults = array(
			'site_uuid'           => '',
			'installation_id'     => '',
			'project_name'        => wp_strip_all_tags( get_bloginfo( 'name' ) ),
			'domain'              => is_string( $host ) ? $host : '',
			'timezone'            => function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : 'UTC',
			'currency'            => 'BRL',
			'logo'                => '',
			'accent_color'        => '#1f3d34',
			'retention_days'      => '365',
			'consent_mode'        => 'cookies',
			'require_consent'     => '0',
			'cmp_integration'     => '0',
			'tracking_enabled'    => '1',
			'license_key'         => '',
			'license_status'      => 'unlicensed',
			'plugin_version'      => MOCORI_INSIGHTS_VERSION,
			'db_version'          => MOCORI_INSIGHTS_DB_VERSION,
			'collect_token'       => '',
			'funnels'             => '[]',
			'channel_rules'       => wp_json_encode( Mocori_Insights_Source_Classifier::default_rules() ),
			'project_configured'  => '0',
		);

		return apply_filters( 'mocori_insights_default_settings', $defaults );
	}

	/**
	 * Seed generated identifiers and defaults once.
	 */
	public function seed_defaults() {
		$current = $this->all();
		$defaults = self::defaults();

		if ( empty( $current['site_uuid'] ) ) {
			$defaults['site_uuid'] = self::uuid();
		} else {
			$defaults['site_uuid'] = $current['site_uuid'];
		}

		if ( empty( $current['installation_id'] ) ) {
			$defaults['installation_id'] = self::uuid();
		} else {
			$defaults['installation_id'] = $current['installation_id'];
		}

		if ( empty( $current['collect_token'] ) ) {
			$defaults['collect_token'] = wp_generate_password( 32, false, false );
		} else {
			$defaults['collect_token'] = $current['collect_token'];
		}

		foreach ( $defaults as $key => $value ) {
			if ( ! array_key_exists( $key, $current ) || '' === (string) $current[ $key ] ) {
				$this->set( $key, $value );
			}
		}

		$this->set( 'plugin_version', MOCORI_INSIGHTS_VERSION );
		$this->cache = null;
	}

	/**
	 * All settings.
	 *
	 * @return array
	 */
	public function all() {
		if ( null !== $this->cache ) {
			return $this->cache;
		}

		global $wpdb;
		$table = Mocori_Insights_Schema::table( 'settings' );
		$rows  = $wpdb->get_results( "SELECT setting_key, setting_value FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$data  = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$data[ $row['setting_key'] ] = $row['setting_value'];
			}
		}
		$this->cache = wp_parse_args( $data, self::defaults() );
		return $this->cache;
	}

	/**
	 * Get one value.
	 *
	 * @param string $key     Key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public function get( $key, $default = '' ) {
		$all = $this->all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Persist one value.
	 *
	 * @param string $key   Key.
	 * @param mixed  $value Value.
	 */
	public function set( $key, $value ) {
		global $wpdb;
		$table = Mocori_Insights_Schema::table( 'settings' );
		$now   = current_time( 'mysql', true );
		$wpdb->replace(
			$table,
			array(
				'setting_key'   => sanitize_key( $key ),
				'setting_value' => is_array( $value ) ? wp_json_encode( $value ) : (string) $value,
				'updated_at'    => $now,
			),
			array( '%s', '%s', '%s' )
		);
		$this->cache = null;
	}

	/**
	 * Update many.
	 *
	 * @param array $pairs Key/value.
	 */
	public function update( $pairs ) {
		foreach ( $pairs as $key => $value ) {
			$this->set( $key, $value );
		}
	}

	/**
	 * Conversion definitions.
	 *
	 * @return array
	 */
	public function conversions() {
		global $wpdb;
		$table = Mocori_Insights_Schema::table( 'conversion_defs' );
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} WHERE is_active = 1 ORDER BY id ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Channel classification rules.
	 *
	 * @return array
	 */
	public function channel_rules() {
		$raw = $this->get( 'channel_rules', '' );
		$decoded = json_decode( (string) $raw, true );
		if ( ! is_array( $decoded ) ) {
			return Mocori_Insights_Source_Classifier::default_rules();
		}
		return $decoded;
	}

	/**
	 * Random UUID v4.
	 *
	 * @return string
	 */
	public static function uuid() {
		$data = random_bytes( 16 );
		$data[6] = chr( ( ord( $data[6] ) & 0x0f ) | 0x40 );
		$data[8] = chr( ( ord( $data[8] ) & 0x3f ) | 0x80 );
		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}
}
