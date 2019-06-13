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

use SmartCAT\WP\Admin\Tables\TableAbstract;
use SmartCAT\WP\DITrait;
use SmartCAT\WP\Helpers\TemplateEngine;

/**
 * Class PageAbstract
 *
 * @package SmartCAT\WP\Admin\Pages
 */
abstract class PageAbstract {
	use DITrait;

	/**
	 * @param $max_page
	 *
	 * @return float|int
	 */
	protected static function get_page( $max_page ) {
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
	protected static function get_paginator_code( $page_name, $max_page, $current_page ) {
		$uri = ! empty( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$url = strtok( $uri, '?' );

		$paginator = '';

		for ( $page_number = 1; $page_number <= $max_page; $page_number ++ ) {
			if ( $page_number === $current_page ) {
				$paginator .= "<span>{$page_number}</span>";
			} else {
				$full_url   = esc_html( $url . "?page={$page_name}&page-number=" . $page_number );
				$paginator .= '<a href="' . $full_url . '">' . $page_number . '</a>';
			}
		}

		return $paginator;
	}

	/**
	 * @param TableAbstract $table
	 * @param array $objects
	 *
	 * @return bool|false|string
	 */
	protected static function get_table_code( $table, $objects ) {
		$table_with_data = $table->set_data( $objects );

		return $table_with_data->display();
	}

	/**
	 * Get Mustache template engine
	 *
	 * @return TemplateEngine|null
	 */
	protected static function get_renderer() {
		$container = self::get_container();

		try {
			return $container->get( 'templater' );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
