<?php

/*
 * @link              https://www.smartcat.ai
 * @package           translation-connectors
 * @wordpress-plugin
 * Plugin Name:       translation-connectors
 * Plugin URI:        https://www.smartcat.ai/api/
 * Description:       WordPress integration to translation connectors.
 * Version:           1.0.7
 * Author:            Smartcat
 * Author URI:        https://www.smartcat.ai
 * License:           GPL-3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       translation-connectors
 * Domain Path:       /languages
 */

//нужно для подключения JS в админке (иначе придется завязываться на относительные пути и огребать)
define('SMARTCAT_PLUGIN_FILE', __FILE__);
define('SMARTCAT_PLUGIN_NAME', 'translation-connectors');

define(
	'SMARTCAT_PLUGIN_DIR',
	realpath( pathinfo( __FILE__, PATHINFO_DIRNAME ) ) . DIRECTORY_SEPARATOR
);

require_once SMARTCAT_PLUGIN_DIR . 'inc/autoload.php';
require_once SMARTCAT_PLUGIN_DIR . 'inc/vendor/a5hleyrich/wp-background-processing/classes/wp-async-request.php';
require_once SMARTCAT_PLUGIN_DIR . 'inc/vendor/a5hleyrich/wp-background-processing/classes/wp-background-process.php';


use SmartCAT\WP\Admin\Settings;

stream_wrapper_register("smartcat", "SmartCAT\WP\VariableStream");

$connector   = new SmartCAT\WP\Connector();
$plugin_data = get_file_data( __FILE__, [ 'Version' => 'Version' ] );

SmartCAT\WP\Connector::$plugin_version = $plugin_data['Version'];

add_action( 'plugins_loaded', [ $connector, 'plugin_load' ], 99 );
add_action( 'init', [ $connector, 'plugin_init' ] );
add_action( 'admin_notices', [ $connector, 'plugin_admin_notice' ], 0 );
add_action( 'admin_menu', [ Settings::class, 'add_admin_menu' ] );
add_action( 'admin_init', [ Settings::class, 'make_settings_page' ] );
register_activation_hook( __FILE__, [ $connector, 'plugin_activate' ] );
register_deactivation_hook( __FILE__, [ $connector, 'plugin_deactivate' ] );

