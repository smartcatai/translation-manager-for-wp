<?php

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// We don't do anything on single sites anyway.
//if ( ! is_multisite() ) {
//	return;
//}

define(
	'SMARTCAT_PLUGIN_DIR',
	realpath( pathinfo( __FILE__, PATHINFO_DIRNAME ) ) . DIRECTORY_SEPARATOR
);
require_once SMARTCAT_PLUGIN_DIR . 'inc/autoload.php';

SmartCAT\WP\Connector::plugin_uninstall();