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
use SmartCAT\WP\WP\Options;

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
		$is_cookie = isset( $_COOKIE['regform'] );
		$options   = self::get_options();

		$statistics_table           = new StatisticsTable();
		$is_statistics_queue_active = boolval( $options->get( 'statistic_queue_active' ) );

		add_thickbox();

		echo self::get_renderer()->render(
			'dashboard',
			[
				'isCookie'         => $is_cookie,
				'button_status'    => $is_statistics_queue_active,
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
			'refresh' => __( 'Refresh statistics', 'translation-connectors' ),
			'title'   => $GLOBALS['title'],
		];
	}

	/**
	 * Get options service
	 *
	 * @return Options|null
	 */
	private static function get_options() {
		$container = self::get_container();

		try {
			return $container->get( 'core.options' );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
