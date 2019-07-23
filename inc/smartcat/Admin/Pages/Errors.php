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
		$errors_table = new ErrorsTable();

		echo self::get_renderer()->render(
			'errors',
			[
				'errors_table' => $errors_table->display(),
				'show_regform' => self::is_need_show_regform(),
				'texts'        => self::get_texts(),
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
			'title'   => $GLOBALS['title'],
		];
	}
}
