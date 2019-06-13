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

use SmartCAT\WP\Admin\Tables\ErrorsTable;
use SmartCAT\WP\DB\Repository\ErrorRepository;

/**
 * Class Errors
 *
 * @package SmartCAT\WP\Admin\Pages
 */
class Errors extends PageAbstract {
	/**
	 * Render errors page
	 */
	public static function render() {
		$errors_repo = self::get_repository();

		$limit = 100;

		$statistics_table  = new ErrorsTable();
		$max_page          = ceil( $errors_repo->get_count() / $limit );
		$page              = self::get_page( $max_page );
		$statistics_result = $errors_repo->get_all( $limit * ( $page - 1 ), $limit );
		$table_code        = self::get_table_code( $statistics_table, $statistics_result );
		$paginator         = self::get_paginator_code( 'sc-errors', $max_page, $page );

		echo self::get_renderer()->render(
			'errors',
			[
				'errors_result'    => $statistics_result ? true : false,
				'errors_table'     => function () use ( $table_code ) {
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
	 * Get errors repository
	 *
	 * @return ErrorRepository|null
	 */
	private static function get_repository() {
		$container = self::get_container();

		try {
			return $container->get( 'entity.repository.error' );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
