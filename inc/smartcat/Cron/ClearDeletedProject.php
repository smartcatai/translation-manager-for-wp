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

/**
 * Class ClearDeletedProject
 *
 * @package SmartCAT\WP\Cron
 */
class ClearDeletedProject extends CronAbstract {
	/**
	 * @return mixed
	 */
	public function get_interval() {
		$schedules['daily'] = [
			'interval' => DAY_IN_SECONDS,
			'display'  => __( 'Once Daily', 'translation-connectors' ),
		];

		return $schedules;
	}

	/**
	 *
	 */
	public function run() {
	}
}
