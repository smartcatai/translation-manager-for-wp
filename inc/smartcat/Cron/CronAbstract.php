<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\Cron;

use SmartCAT\WP\WP\PluginInterface;

abstract class CronAbstract implements CronInterface, PluginInterface {

    public function __construct() {
        add_action($this->get_hook_name(), [ $this, 'run' ]);
    }

    /** Получение имени интервала */
    public function get_interval_name() {
        $interval = $this->get_interval();
        $keys     = array_keys($interval);

        return $keys[0];
    }

    public function get_hook_name() {
        return get_class($this). "_hook";
    }

    public function plugin_activate() {
        wp_clear_scheduled_hook($this->get_hook_name());
        wp_schedule_event(time(), $this->get_interval_name(), $this->get_hook_name());
    }

    public function plugin_deactivate() {
        wp_clear_scheduled_hook($this->get_hook_name());
    }

    public function plugin_uninstall() {
    }

}