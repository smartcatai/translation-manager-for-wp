<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 16.06.2017
 * Time: 12:49
 */

namespace SmartCAT\WP\WP;


interface PluginInterface {
	public function plugin_activate();

	public function plugin_deactivate();

	public function plugin_uninstall();
}