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
use SmartCAT\WP\DITrait;
use SmartCAT\WP\Helpers\TemplateEngine;

/**
 * Class Dashboard
 *
 * @package SmartCAT\WP\Admin\Pages
 */
class Dashboard {
	use DITrait;

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

		add_thickbox();

		echo $render->render(
			'dashboard',
			[
				'title'            => $GLOBALS['title'],
				'isCookie'         => $is_cookie,
				'button_status'    => $is_statistics_queue_active ? 'disabled="disabled"' : false,
				'statistic_result' => $statistics_result ? true : false,
				'refresh_text'     => __( 'Refresh statistics', 'translation-connectors' ),
				'statistic_table'  => function () use ( $statistics_table, $render, $statistics_result ) {
					$table_with_data = $statistics_table->set_data( $statistics_result );

					return $render->ob_to_string( [ $table_with_data, 'display' ] );
				},
				'pages_text'       => __( 'Pages', 'translation-connectors' ),
				'empty_message'    => __( 'Statistics is empty', 'translation-connectors' ),
				'paginator'        => function () use ( $max_page, $page ) {
					return self::get_paginator_code( $max_page, $page );
				},
			]
		);
	}

	/**
	 * @param $max_page
	 *
	 * @return float|int
	 */
	private static function get_page( $max_page ) {
		$page = isset( $_GET['page-number'] ) ? abs( intval( $_GET['page-number'] ) ) : 1;
		$page = ( $page > $max_page ) ? $max_page : $page;
		$page = ( $page >= 1 ) ? $page : 1;

		return $page;
	}

	/**
	 * @param int $max_page
	 * @param int $page
	 *
	 * @return string
	 */
	private static function get_paginator_code( $max_page, $page ) {
		$uri = ! empty( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$url = strtok( $uri, '?' );

		$paginator = '';

		for ( $page_number = 1; $page_number <= $max_page; $page_number ++ ) {
			if ( $page_number === $page ) {
				$paginator .= "<span>{$page_number}</span>";
			} else {
				$full_url   = esc_html( $url . '?page=sc-translation-progress&page-number=' . $page_number );
				$paginator .= '<a href="' . $full_url . '">' . $page_number . '</a>';
			}
		}

		return $paginator;
	}
}
