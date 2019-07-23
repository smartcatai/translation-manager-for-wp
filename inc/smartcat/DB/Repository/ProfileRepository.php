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

use SmartCAT\WP\DB\Entity\Profile;

/**
 * Class ProfileRepository
 *
 * @method Profile get_one_by_id( int $id )
 * @method Profile[] get_all_by( array $criterias )
 * @method Profile get_one_by( array $criterias )
 * @method Profile[] get_all( $from = 0, $limit = 0 )
 *
 * @package SmartCAT\WP\DB\Repository
 */
class ProfileRepository extends RepositoryAbstract {
	const TABLE_NAME = 'profiles';

	/**
	 * Get entity table name
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->prefix . self::TABLE_NAME;
	}

	/**
	 * Serialize RAW data from DB to Profile object
	 *
	 * @param \stdClass $row RAW data from DB in stdClass.
	 * @return Profile
	 */
	protected function to_entity( $row ) {
		return new Profile( $row );
	}

	/**
	 * Flush data
	 *
	 * @param Profile[] $persists Persists.
	 */
	protected function do_flush( array $persists ) {
		foreach ( $persists as $profile ) {
			if ( $profile instanceof Profile ) {
				if ( empty( $profile->get_id() ) ) {
					$res = $this->add( $profile );

					if ( $res ) {
						$profile->set_id( $res );
					}
				} else {
					$this->update( $profile );
				}
			}
		}
	}
}
