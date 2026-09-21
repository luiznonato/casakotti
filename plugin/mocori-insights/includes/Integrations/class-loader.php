<?php
/**
 * Integration loader. Core stays generic.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integrations.
 */
class Mocori_Insights_Integrations {
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
	 * Hooks. Future modules (Woo, CF7, Elementor, CRM) attach here.
	 */
	public function hooks() {
		add_action( 'mocori_insights_loaded', array( $this, 'seed_from_filters' ), 20 );
		do_action( 'mocori_insights_register_integrations', $this->plugin );
	}

	/**
	 * Allow client plugins to seed conversion definitions once.
	 */
	public function seed_from_filters() {
		$defs = apply_filters( 'mocori_insights_seed_conversions', array() );
		if ( ! is_array( $defs ) || ! $defs ) {
			return;
		}
		$existing = $this->plugin->settings->conversions();
		$engine   = new Mocori_Insights_Conversion_Engine( $this->plugin );
		foreach ( $defs as $def ) {
			if ( empty( $def['event_name'] ) || empty( $def['conversion_name'] ) ) {
				continue;
			}
			$already = false;
			foreach ( $existing as $row ) {
				if ( $row['event_name'] === $def['event_name'] && $row['match_value'] === ( isset( $def['match_value'] ) ? $def['match_value'] : '' ) && $row['conversion_name'] === $def['conversion_name'] ) {
					$already = true;
					break;
				}
			}
			if ( $already ) {
				continue;
			}
			$engine->save_definition(
				array(
					'event_name'      => $def['event_name'],
					'match_key'       => isset( $def['match_key'] ) ? $def['match_key'] : '',
					'match_value'     => isset( $def['match_value'] ) ? $def['match_value'] : '',
					'conversion_name' => $def['conversion_name'],
					'conversion_type' => isset( $def['conversion_type'] ) ? $def['conversion_type'] : 'lead',
					'is_active'       => 1,
				)
			);
		}
	}
}
