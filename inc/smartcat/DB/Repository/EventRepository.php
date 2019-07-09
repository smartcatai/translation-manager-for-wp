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

use SmartCAT\WP\DB\Entity\Event;

/**
 * Class EventRepository
 *
 * @method Event get_one_by_id( int $id )
 * @method Event[] get_all_by( array $criterias )
 * @method Event get_one_by( array $criterias )
 *
 * @package SmartCAT\WP\DB\Repository
 */
class EventRepository extends RepositoryAbstract {
	const TABLE_NAME = 'errors';

	/**
	 * @return string
	 */
	public function get_table_name() {
		return $this->prefix . self::TABLE_NAME;
	}

	/**
	 * @param Event[] $persists
	 *
	 * @return mixed|void
	 */
	protected function do_flush( array $persists ) {
		foreach ( $persists as $error ) {
			if ( get_class( $error ) === Event::class ) {
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
	 * @return Event
	 */
	protected function to_entity( $row ) {
		return new Event( $row );
	}
}
