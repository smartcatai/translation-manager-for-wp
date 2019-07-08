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

use SmartCAT\WP\DB\Entity\Task;

/**
 * Class TaskRepository
 *
 * @method Task get_one_by_id( int $id )
 * @method Task[] get_all_by( array $criterias )
 * @method Task get_one_by( array $criterias )
 * @method Task[] get_all( $from = 0, $limit = 0 )
 *
 * @package SmartCAT\WP\DB\Repository
 */
class TaskRepository extends RepositoryAbstract {
	const TABLE_NAME = 'tasks';

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->prefix . self::TABLE_NAME;
	}

	/**
	 * @param Task[] $persists
	 *
	 * @return mixed|void
	 */
	protected function do_flush( array $persists ) {
		foreach ( $persists as $task ) {
			if ( $task instanceof Task ) {
				if ( empty( $task->get_id() ) ) {
					$res = $this->add( $task );
					if ( $res ) {
						$task->set_id( $res );
					}
				} else {
					$this->update( $task );
				}
			}
		}
	}

	/**
	 * @param $row
	 *
	 * @return mixed|Task
	 */
	protected function to_entity( $row ) {
		return new Task( $row );
	}
}
