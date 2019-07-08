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
 * Class EntityAbstract
 *
 * @package SmartCAT\WP\DB\Entity
 */
abstract class EntityAbstract implements EntityInterface {
	/**
	 * EntityInterface constructor.
	 *
	 * @param \stdClass $raw_data
	 */
	public function __construct( \stdClass $raw_data = null ) {
		if ( empty( $raw_data ) ) {
			return;
		}

		foreach ( $this->attributes() as $db_row => $private_field ) {
			if ( isset( $raw_data->$db_row ) ) {
				$this->$private_field = $raw_data->$db_row;
			}
		}
	}

	/**
	 * @return array
	 */
	public function get_raw_data(): array {
		$raw_data = [];

		foreach ( $this->attributes() as $db_row => $private_field ) {
			if ( 'id' === $db_row ) {
				continue;
			}

			if ( isset( $this->$private_field ) ) {
				$raw_data = array_merge(
					$raw_data,
					[
						$db_row => $this->$private_field,
					]
				);
			}
		}

		return $raw_data;
	}
}
