<?php
/**
 * Smartcat Translation Manager for WordPress
 *
 * @package Smartcat Translation Manager for WordPress
 * @author Smartcat <support@smartcat.ai>
 * @copyright (c) 2019 Smartcat. All Rights Reserved.
 * @license GNU General Public License version 3 or later; see LICENSE.txt
 * @link http://smartcat.ai
 */

namespace SmartCAT\WP\Cron;

use SmartCAT\WP\Connector;
use SmartCAT\WP\WP\Options;
use SmartCAT\WP\WP\PluginInterface;

/**
 * Class CronAbstract
 *
 * @package SmartCAT\WP\Cron
 */
abstract class CronAbstract implements CronInterface, PluginInterface {
	/**
	 * CronAbstract constructor.
	 */
	public function __construct() {
		add_action( $this->get_hook_name(), [ $this, 'run' ] );
	}

	/**
	 * @return mixed
	 */
	public function get_interval_name() {
		$interval = $this->get_interval();
		$keys     = array_keys( $interval );

		return $keys[0];
	}

	/**
	 * @return string
	 */
	public function get_hook_name() {
		return get_class( $this ) . "_hook";
	}

	/**
	 *
	 */
	public function plugin_activate() {
		/** @var Options $options */
		$options = Connector::get_container()->get( 'core.options' );

		if ( ! $options->get( 'use_external_cron' ) ) {
			$this->register();
		}
	}

	/**
	 *
	 */
	public function register() {
		wp_clear_scheduled_hook( $this->get_hook_name() );
		wp_schedule_event( time(), $this->get_interval_name(), $this->get_hook_name() );
	}

	/**
	 *
	 */
	public function unregister() {
		wp_clear_scheduled_hook( $this->get_hook_name() );
	}

	/**
	 *
	 */
	public function plugin_deactivate() {
		$this->unregister();
	}

	/**
	 *
	 */
	public function plugin_uninstall() {
	}
}
