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

namespace SmartCAT\WP\DB\Entity;

/**
 * Interface EntityInterface
 *
 * @package SmartCAT\WP\DB\Entity
 */
interface EntityInterface {
	/**
	 * EntityInterface constructor.
	 *
	 * @param \stdClass $raw_data Raw data from WP database.
	 */
	public function __construct( \stdClass $raw_data = null );

	/**
	 * @return array
	 */
	public function attributes(): array;

	/**
	 * @return array
	 */
	public function get_raw_data(): array;

	/**
	 * @return int
	 */
	public function get_id();
}
