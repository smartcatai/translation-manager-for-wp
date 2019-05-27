<?php

/*
 * @link              https://www.smartcat.ai
 * @package           translation-connectors
 * @wordpress-plugin
 * Plugin Name:       Smartcat Translation Manager
 * Plugin URI:        https://www.smartcat.ai/api/
 * Description:       WordPress integration to translation connectors.
 * Version:           1.2.1
 * Author:            Smartcat
 * Author URI:        https://www.smartcat.ai
 * License:           GPL-3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       translation-connectors
 * Domain Path:       /languages
 */

//нужно для подключения JS в админке (иначе придется завязываться на относительные пути и огребать)
define('SMARTCAT_PLUGIN_FILE', __FILE__);
define('SMARTCAT_PLUGIN_NAME', basename(__DIR__));

define(
    'SMARTCAT_PLUGIN_DIR',
    realpath(pathinfo(__FILE__, PATHINFO_DIRNAME)) . DIRECTORY_SEPARATOR
);

define('SMARTCAT_DEBUG_LOG', SMARTCAT_PLUGIN_DIR . 'debug.log');
define('SMARTCAT_DEBUG_ENABLED', false);

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

if (version_compare(PHP_VERSION, '7.0.0') < 0) {
    deactivate_plugins(plugin_basename(__FILE__), false);
    wp_die(
        __('You PHP version is incompatible. Plugin works only on PHP 7.0 or higher.' , 'translation-connectors'),
        __('Smartcat Translation Manager Error' , 'translation-connectors'),
        ['back_link' => true]
   );
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

if (version_compare(PHP_VERSION, '7.0.0') < 0) {
    deactivate_plugins(plugin_basename(__FILE__), false);
    wp_die(
        __('You PHP version is incompatible. Plugin works only on PHP 7.0 or higher.' , 'translation-connectors'),
        __('Smartcat Translation Manager Error' , 'translation-connectors'),
        ['back_link' => true]
   );
}

require_once SMARTCAT_PLUGIN_DIR . 'inc/autoload.php';
require_once SMARTCAT_PLUGIN_DIR . 'inc/vendor/a5hleyrich/wp-background-processing/classes/wp-async-request.php';
require_once SMARTCAT_PLUGIN_DIR . 'inc/vendor/a5hleyrich/wp-background-processing/classes/wp-background-process.php';

use SmartCAT\WP\Admin\Settings;

stream_wrapper_register("smartcat", "SmartCAT\WP\VariableStream");

$connector   = new SmartCAT\WP\Connector();
$plugin_data = get_file_data(__FILE__, [ 'Version' => 'Version' ]);

SmartCAT\WP\Connector::$plugin_version = $plugin_data['Version'];

add_action('plugins_loaded', [ $connector, 'plugin_load' ], 99);

if (!$connector::check_dependency()) {
    deactivate_plugins(plugin_basename(__FILE__), false);
    wp_die(
        __('You need to activate the Polylang plugin in order to use Smartcat Translation Manager' , 'translation-connectors'),
        __('Smartcat Translation Manager Error' , 'translation-connectors'),
        ['back_link' => true]
   );
} else {
    add_action('init', [ $connector, 'plugin_init' ]);
    add_action('admin_notices', [ $connector, 'plugin_admin_notice' ], 0);
    add_action('admin_menu', [ Settings::class, 'add_admin_menu' ]);
    add_action('admin_init', [ Settings::class, 'make_settings_page' ]);
    add_action('post_updated', [ $connector, 'post_update_hook' ], 10, 3);
    register_activation_hook(__FILE__, [ $connector, 'plugin_activate' ]);
    register_deactivation_hook(__FILE__, [ $connector, 'plugin_deactivate' ]);
}
