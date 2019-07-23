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

namespace SmartCAT\WP\Helpers;

/**
 * Class TemplateEngine
 *
 * @package SmartCAT\WP\Helpers
 */
class TemplateEngine extends \Mustache_Engine {
	/**
	 * TemplateEngine constructor.
	 *
	 * @param array $options Mustache additional options.
	 */
	public function __construct( array $options = array() ) {
		$options = array_merge(
			$options,
			[
				'loader'          => new \Mustache_Loader_FilesystemLoader( SMARTCAT_PLUGIN_DIR . 'views', [ 'extension' => '.html' ] ),
				'partials_loader' => new \Mustache_Loader_FilesystemLoader( SMARTCAT_PLUGIN_DIR . 'views/partials', [ 'extension' => '.html' ] ),
			]
		);
		parent::__construct( $options );
	}

	/**
	 * Return every ECHO function into string
	 *
	 * @param callable $function Function to call.
	 * @param mixed    ...$args Function arguments if needed.
	 *
	 * @return bool|false|string
	 */
	public function ob_to_string( $function, ...$args ) {
		try {
			ob_start();
			call_user_func_array( $function, $args );
			return ob_get_clean();
		} catch ( \Throwable $e ) {
			Logger::warning( "Can't call user func to string.", "Reason: {$e->getMessage()}" );
		}

		return false;
	}
}
