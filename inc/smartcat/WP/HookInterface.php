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

namespace SmartCAT\WP\WP;

/**
 * Interface HookInterface
 *
 * @package SmartCAT\WP\WP
 */
interface HookInterface {
	/**
	 * Register hooks function
	 *
	 * @return mixed
	 */
	public function register_hooks();
}
