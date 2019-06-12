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
use SmartCAT\WP\Helpers\TemplateEngine;

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
		$container = self::get_container();

		/** @var TemplateEngine $render */
		$render    = $container->get( 'templater' );
		$stat_repo = $container->get( 'entity.repository.statistic' );
		$options   = $container->get( 'core.options' );

		$limit = 100;

		$statistics_table           = new StatisticsTable();
		$max_page                   = ceil( $stat_repo->get_count() / $limit );
		$page                       = self::get_page( $max_page );
		$is_statistics_queue_active = boolval( $options->get( 'statistic_queue_active' ) );
		$statistics_result          = $stat_repo->get_statistics( $limit * ( $page - 1 ), $limit );
		$table_code                 = self::get_table_code( $statistics_table, $statistics_result );
		$paginator                  = self::get_paginator_code( 'sc-dashboard', $max_page, $page );

		add_thickbox();

		echo $render->render(
			'dashboard',
			[
				'isCookie'         => $is_cookie,
				'button_status'    => $is_statistics_queue_active ? 'disabled="disabled"' : false,
				'statistic_result' => $statistics_result ? true : false,
				'statistic_table'  => function () use ( $table_code ) {
					return $table_code;
				},
				'texts'            => self::get_texts(),
				'paginator'        => function () use ( $paginator ) {
					return $paginator;
				},
			]
		);
	}

	/**
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
}
