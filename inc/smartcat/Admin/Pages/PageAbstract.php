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
