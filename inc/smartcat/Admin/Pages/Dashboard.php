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
use SmartCAT\WP\DB\Repository\StatisticRepository;
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

		$stat_repo = self::get_repository();
		$options   = self::get_options();

		$limit = 100;

		$statistics_table           = new StatisticsTable();
		$max_page                   = ceil( $stat_repo->get_count() / $limit );
		$page                       = self::get_page( $max_page );
		$is_statistics_queue_active = boolval( $options->get( 'statistic_queue_active' ) );
		$statistics_result          = $stat_repo->get_statistics( $limit * ( $page - 1 ), $limit );

		add_thickbox();

		echo self::get_renderer()->render(
			'dashboard',
			[
				'isCookie'         => $is_cookie,
				'button_status'    => $is_statistics_queue_active ? 'disabled="disabled"' : false,
				'statistic_result' => $statistics_result ? true : false,
				'statistic_table'  => self::get_table_code( $statistics_table, $statistics_result ),
				'texts'            => self::get_texts(),
				'paginator'        => self::get_paginator_code( 'sc-dashboard', $max_page, $page ),
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
			'pages'   => __( 'Pages', 'translation-connectors' ),
			'empty'   => __( 'Statistics is empty', 'translation-connectors' ),
			'title'   => $GLOBALS['title'],
		];
	}

	/**
	 * Get statistics repository
	 *
	 * @return StatisticRepository|null
	 */
	private static function get_repository() {
		$container = self::get_container();

		try {
			return $container->get( 'entity.repository.statistic' );
		} catch ( \Exception $e ) {
			return null;
		}
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
