<?php
/**
 * Smartcat Translation Manager for WordPress
 *
 * @wordpress-plugin
 * @link            https://www.smartcat.ai
 * @package         translation-connectors
 * Plugin Name:     Smartcat Translation Manager
 * Plugin URI:      https://www.smartcat.ai/api/
 * Description:     WordPress integration to translation connectors.
 * Version:         2.1.1
 * Author:          Smartcat
 * Author URI:      https://www.smartcat.ai
 * License:         GPL-3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:     translation-connectors
 * Domain Path:     /languages
 */

define( 'SMARTCAT_PLUGIN_FILE', __FILE__ );
define( 'SMARTCAT_PLUGIN_NAME', basename( __DIR__ ) );

define(
	'SMARTCAT_PLUGIN_DIR',
	realpath( pathinfo( __FILE__, PATHINFO_DIRNAME ) ) . DIRECTORY_SEPARATOR
);

define( 'SMARTCAT_DEBUG_LOG', SMARTCAT_PLUGIN_DIR . 'debug.log' );

require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( version_compare( PHP_VERSION, '7.0.0' ) < 0 ) {
	deactivate_plugins( plugin_basename( __FILE__ ), false );
	wp_die(
		esc_html__( 'You PHP version is incompatible. Plugin works only on PHP 7.0 or higher.', 'translation-connectors' ),
		esc_html__( 'Smartcat Translation Manager Error', 'translation-connectors' ),
		[ 'back_link' => true ]
	);
}

require_once SMARTCAT_PLUGIN_DIR . 'inc/autoload.php';
require_once SMARTCAT_PLUGIN_DIR . 'inc/vendor/a5hleyrich/wp-background-processing/classes/wp-async-request.php';
require_once SMARTCAT_PLUGIN_DIR . 'inc/vendor/a5hleyrich/wp-background-processing/classes/wp-background-process.php';

use SmartCAT\WP\Admin\Menu;
use SmartCAT\WP\Admin\Pages\Settings;

stream_wrapper_register( 'smartcat', 'SmartCAT\WP\VariableStream' );

$connector   = new SmartCAT\WP\Connector();
$plugin_data = get_file_data( __FILE__, [ 'Version' => 'Version' ] );

$default_priority = 10;
$accepted_args    = 3;

SmartCAT\WP\Connector::$plugin_version = $plugin_data['Version'];

add_action( 'plugins_loaded', [ $connector, 'plugin_load' ], 99 );

if ( ! $connector::check_dependency() ) {
	deactivate_plugins( plugin_basename( __FILE__ ), false );
	wp_die(
		esc_html__( 'You need to activate the Polylang plugin in order to use Smartcat Translation Manager', 'translation-connectors' ),
		esc_html__( 'Smartcat Translation Manager Error', 'translation-connectors' ),
		[ 'back_link' => true ]
	);
} else {
	add_action( 'init', [ $connector, 'plugin_init' ] );
	add_action( 'admin_notices', [ $connector, 'plugin_admin_notice' ], 0 );
	add_filter(
		'set-screen-option',
		function( $status, $option, $value ) {
			return ( in_array( $option, [ 'sc_profiles_per_page', 'sc_dashboard_per_page', 'sc_errors_per_page', 'sc_events_per_page' ], true ) ) ? (int) $value : $status;
		},
		$default_priority,
		$accepted_args
	);
	add_action( 'admin_menu', [ Menu::class, 'add_admin_menu' ] );
	add_action( 'admin_init', [ Settings::class, 'make_page' ] );
	add_action( 'upgrader_process_complete', [ $connector, 'plugin_upgrade' ], 10, 2 );
	register_activation_hook( __FILE__, [ $connector, 'plugin_activate' ] );
	register_deactivation_hook( __FILE__, [ $connector, 'plugin_deactivate' ] );
}
