<?php
/**
 * CSV export.
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exporter.
 */
class Mocori_Insights_Exporter {
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
	 * Hooks.
	 */
	public function hooks() {
		add_action( 'admin_post_mocori_insights_export', array( $this, 'handle' ) );
	}

	/**
	 * Handle export.
	 */
	public function handle() {
		if ( ! Mocori_Insights_Capabilities::can_view() ) {
			wp_die( esc_html__( 'Acesso negado.', 'mocori-insights' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'mocori_insights_export' );

		$type   = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'visitors';
		$preset = isset( $_GET['range'] ) ? sanitize_key( wp_unslash( $_GET['range'] ) ) : '30d';
		$from   = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
		$to     = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
		$range  = Mocori_Insights_Metrics::range( $preset, $from, $to );
		$report = new Mocori_Insights_Reports( $this->plugin );

		switch ( $type ) {
			case 'sessions':
				$rows = $report->sessions( $range, 5000 );
				break;
			case 'events':
				$rows = $report->events( $range, 5000 );
				break;
			case 'conversions':
				$rows = $report->conversions( $range, 5000 );
				break;
			case 'campaigns':
				$rows = $report->campaigns( $range );
				break;
			case 'pages':
				$rows = $report->pages( $range );
				break;
			case 'visitors':
			default:
				$type = 'visitors';
				$rows = $report->visitors( $range, 5000 );
				break;
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="mocori-insights-' . $type . '-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fputs( $out, "\xEF\xBB\xBF" );
		if ( $rows ) {
			fputcsv( $out, array_keys( $rows[0] ) );
			foreach ( $rows as $row ) {
				fputcsv( $out, array_map( array( $this, 'safe' ), $row ) );
			}
		}
		fclose( $out );
		exit;
	}

	/**
	 * Neutralize CSV formulas.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public function safe( $value ) {
		$value = (string) $value;
		if ( preg_match( '/^[\s]*[=+\-@]/u', $value ) ) {
			return "'" . $value;
		}
		return $value;
	}
}
