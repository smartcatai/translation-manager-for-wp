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
use SmartCAT\WP\WP\Options;
use \Exception;

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
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * @return bool
	 */
	protected static function is_need_show_regform() {
		$options = self::get_options();

		if ( $options ) {
			$ret = ! (bool) $options->get( 'smartcat_regform_showed' );

			if ( $ret ) {
				add_thickbox();
				$options->set( 'smartcat_regform_showed', true );
			}

			return $ret;
		}

		return true;
	}

	/**
	 * Get options service
	 *
	 * @return Options|null
	 */
	protected static function get_options() {
		$container = self::get_container();

		try {
			return $container->get( 'core.options' );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
