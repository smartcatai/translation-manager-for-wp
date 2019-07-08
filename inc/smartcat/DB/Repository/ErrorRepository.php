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

namespace SmartCAT\WP\DB\Repository;

use SmartCAT\WP\DB\Entity\Error;

/**
 * Class ErrorRepository
 *
 * @method Error get_one_by_id( int $id )
 * @method Error[] get_all_by( array $criterias )
 * @method Error get_one_by( array $criterias )
 *
 * @package SmartCAT\WP\DB\Repository
 */
class ErrorRepository extends RepositoryAbstract {
	const TABLE_NAME = 'errors';

	/**
	 * @return string
	 */
	public function get_table_name() {
		return $this->prefix . self::TABLE_NAME;
	}

	/**
	 * @param int $from
	 * @param int $limit
	 *
	 * @return Error[]
	 */
	public function get_all( $from = 0, $limit = 0 ) {
		$wpdb  = $this->get_wp_db();
		$from  = intval( $from );
		$limit = intval( $limit );

		$table_name = $this->get_table_name();
		$query      = "SELECT * FROM $table_name";

		if ( $limit > 0 ) {
			$query = $wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY date DESC LIMIT %d, %d",
				[ $from, $limit ]
			);
		}

		$results = $wpdb->get_results( $query );

		return $this->prepare_result( $results );
	}

	/**
	 * @param Error[] $persists
	 *
	 * @return mixed|void
	 */
	protected function do_flush( array $persists ) {
		foreach ( $persists as $error ) {
			if ( get_class( $error ) === Error::class ) {
				if ( empty( $error->get_id() ) ) {
					$res = $this->add( $error );
					if ( $res ) {
						$error->set_id( $res );
					}
				} else {
					$this->update( $error );
				}
			}
		}
	}

	/**
	 * @param $row
	 *
	 * @return mixed|Error
	 */
	protected function to_entity( $row ) {
		return new Error( $row );
	}
}
