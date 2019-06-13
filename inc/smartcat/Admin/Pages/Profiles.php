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

use SmartCAT\WP\Admin\Tables\ProfilesTable;
use SmartCAT\WP\DB\Repository\ProfileRepository;

/**
 * Class Profiles
 *
 * @package SmartCAT\WP\Admin\Pages
 */
class Profiles extends PageAbstract {
	/**
	 * Render profiles page
	 */
	public static function render() {
		$profiles_repo = self::get_repository();

		$limit = 100;

		$profiles_table  = new ProfilesTable();
		$max_page        = ceil( $profiles_repo->get_count() / $limit );
		$page            = self::get_page( $max_page );
		$profiles_result = $profiles_repo->get_all( $limit * ( $page - 1 ), $limit );
		$table_code      = self::get_table_code( $profiles_table, $profiles_result );
		$paginator       = self::get_paginator_code( 'sc-profiles', $max_page, $page );

		echo self::get_renderer()->render(
			'profiles',
			[
				'texts'           => self::get_texts(),
				'profiles_result' => $profiles_result ? true : false,
				'profiles_table'  => function () use ( $table_code ) {
					return $table_code;
				},
				'paginator'       => function () use ( $paginator ) {
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
			'add_profile' => __( 'New profile', 'translation-connectors' ),
			'empty'       => __( 'Profiles not found', 'translation-connectors' ),
			'pages'       => __( 'Pages', 'translation-connectors' ),
			'title'       => $GLOBALS['title'],
		];
	}

	/**
	 * Get errors repository
	 *
	 * @return ProfileRepository|null
	 */
	private static function get_repository() {
		$container = self::get_container();

		try {
			return $container->get( 'entity.repository.profile' );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
