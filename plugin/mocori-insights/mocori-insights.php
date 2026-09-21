<?php
/**
 * Plugin Name: Mocori Insights
 * Plugin URI: https://mocori.com.br
 * Description: Analytics proprietário da Mocori para visitantes, sessões, aquisição, eventos, conversões e dashboards em qualquer WordPress.
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Mocori
 * Author URI: https://mocori.com.br
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mocori-insights
 *
 * @package Mocori_Insights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MOCORI_INSIGHTS_VERSION', '1.0.0' );
define( 'MOCORI_INSIGHTS_DB_VERSION', '1.0.0' );
define( 'MOCORI_INSIGHTS_FILE', __FILE__ );
define( 'MOCORI_INSIGHTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOCORI_INSIGHTS_URL', plugin_dir_url( __FILE__ ) );
define( 'MOCORI_INSIGHTS_SLUG', 'mocori-insights' );

require_once MOCORI_INSIGHTS_DIR . 'includes/autoload.php';

/**
 * Plugin instance.
 *
 * @return Mocori_Insights_Plugin
 */
function mocori_insights() {
	return Mocori_Insights_Plugin::instance();
}

register_activation_hook( __FILE__, array( 'Mocori_Insights_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Mocori_Insights_Install', 'deactivate' ) );

add_action( 'plugins_loaded', 'mocori_insights', 5 );
