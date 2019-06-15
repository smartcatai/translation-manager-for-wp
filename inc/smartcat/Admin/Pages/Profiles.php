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
		$profiles_table = new ProfilesTable();

		echo self::get_renderer()->render(
			'profiles',
			[
				'texts'           => self::get_texts(),
				'profiles_table'  => $profiles_table->display(),
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
			'pages'       => __( 'Pages', 'translation-connectors' ),
			'title'       => $GLOBALS['title'],
		];
	}
}
