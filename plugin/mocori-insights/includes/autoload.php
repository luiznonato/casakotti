<?php
/**
 * Class map autoloader.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'Mocori_Insights_' ) ) {
			return;
		}

		$map = array(
			'Mocori_Insights_Plugin'             => 'class-plugin.php',
			'Mocori_Insights_Install'            => 'Core/class-install.php',
			'Mocori_Insights_Capabilities'       => 'Core/class-capabilities.php',
			'Mocori_Insights_Settings'           => 'Core/class-settings.php',
			'Mocori_Insights_Schema'             => 'Database/class-schema.php',
			'Mocori_Insights_Migrator'           => 'Database/class-migrator.php',
			'Mocori_Insights_Repository'         => 'Database/class-repository.php',
			'Mocori_Insights_Collector'          => 'Tracking/class-collector.php',
			'Mocori_Insights_Visitor_Service'    => 'Tracking/class-visitor-service.php',
			'Mocori_Insights_Session_Service'    => 'Tracking/class-session-service.php',
			'Mocori_Insights_Event_Service'      => 'Tracking/class-event-service.php',
			'Mocori_Insights_Attribution'        => 'Tracking/class-attribution.php',
			'Mocori_Insights_Source_Classifier'  => 'Tracking/class-source-classifier.php',
			'Mocori_Insights_User_Agent'         => 'Tracking/class-user-agent.php',
			'Mocori_Insights_Conversion_Engine'  => 'Tracking/class-conversion-engine.php',
			'Mocori_Insights_Sanitizer'          => 'Tracking/class-sanitizer.php',
			'Mocori_Insights_Metrics'            => 'Analytics/class-metrics.php',
			'Mocori_Insights_Aggregator'         => 'Analytics/class-aggregator.php',
			'Mocori_Insights_Realtime'           => 'Analytics/class-realtime.php',
			'Mocori_Insights_Reports'            => 'Analytics/class-reports.php',
			'Mocori_Insights_Funnel'             => 'Analytics/class-funnel.php',
			'Mocori_Insights_Consent'            => 'Privacy/class-consent.php',
			'Mocori_Insights_Retention'          => 'Privacy/class-retention.php',
			'Mocori_Insights_Eraser'             => 'Privacy/class-eraser.php',
			'Mocori_Insights_Admin'              => 'Admin/class-admin.php',
			'Mocori_Insights_Rest'               => 'API/class-rest.php',
			'Mocori_Insights_Frontend'           => 'API/class-frontend.php',
			'Mocori_Insights_Public_API'         => 'API/class-public-api.php',
			'Mocori_Insights_Exporter'           => 'Reports/class-exporter.php',
			'Mocori_Insights_Integrations'       => 'Integrations/class-loader.php',
		);

		if ( empty( $map[ $class ] ) ) {
			return;
		}

		$path = MOCORI_INSIGHTS_DIR . 'includes/' . $map[ $class ];
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);
