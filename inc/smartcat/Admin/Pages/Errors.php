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

use SmartCAT\WP\DITrait;
use SmartCAT\WP\Helpers\TemplateEngine;

/**
 * Class Errors
 *
 * @package SmartCAT\WP\Admin\Pages
 */
class Errors {
	use DITrait;

	/**
	 * Render errors page
	 */
	public static function render() {
		$container = self::get_container();

		/** @var TemplateEngine $render */
		$render = $container->get( 'templater' );
		echo $render->render(
			'errors',
			[
				'title'            => $GLOBALS['title'],
			]
		);
	}
}
