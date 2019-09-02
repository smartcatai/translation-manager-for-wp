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

namespace SmartCAT\WP\Admin\Pages;

use SmartCAT\WP\Admin\Tables\StatisticsTable;

/**
 * Class Dashboard
 *
 * @package SmartCAT\WP\Admin\Pages
 */
class Dashboard extends PageAbstract {
	/**
	 * Render dasboard page
	 */
	public static function render() {
		$statistics_table = new StatisticsTable();

		echo self::get_renderer()->render(
			'dashboard',
			[
				'show_regform'     => self::is_need_show_regform(),
				'statistic_table'  => $statistics_table->display(),
				'texts'            => self::get_texts(),
			]
		);
	}

	/**
	 * Get texts array for render
	 *
	 * @return array
	 */
	private static function get_texts() {
		return [
			'refresh' => __( 'Synchronize', 'translation-connectors' ),
			'title'   => $GLOBALS['title'],
		];
	}
}
