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
 * @package SmartCAT\WP\DB\Repository
 */
class TaskRepository extends RepositoryAbstract {
	const TABLE_NAME = 'tasks';

	/**
	 * @return string
	 */
	public function get_table_name() {
		return $this->prefix . self::TABLE_NAME;
	}

	/**
	 * @return Task[]
	 */
	public function get_new_task() {
		return $this->get_tasks_by_status( Task::STATUS_NEW );
	}

	/**
	 * @param $status string|array
	 * @return Task[]
	 */
	public function get_tasks_by_status( $status ) {
		$table_name = $this->get_table_name();

		if ( is_array( $status ) ) {
			$status = implode( "', OR status='", $status );
		}

		$query = sprintf(
			"SELECT * FROM %s WHERE status='%s'",
			$table_name,
			$status
		);

		$results = $this->get_wp_db()->get_results( $query );

		return $this->prepare_result( $results );
	}

	/**
	 * @param Task $task
	 *
	 * @return bool|int
	 */
	public function add( $task ) {
		$table_name = $this->get_table_name();
		$wpdb	   = $this->get_wp_db();

		$data = [
			'sourceLanguage'  => $task->get_source_language(),
			'targetLanguages' => serialize( $task->get_target_languages() ),
			'status'          => $task->get_status(),
			'projectID'       => $task->get_project_id(),
			'profileID'       => intval( $task->get_profile_id() ),
		];

		if ( ! empty( $task->get_id() ) ) {
			$data['id'] = $task->get_id();
		}

		if ( $wpdb->insert( $table_name, $data ) ) {
			$task->set_id( $wpdb->insert_id );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * @param Task $task
	 *
	 * @return bool
	 */
	public function update( $task ) {
		$table_name = $this->get_table_name();
		$wpdb	   = $this->get_wp_db();

		if ( ! empty( $task->get_id() ) ) {
			$data = [
				'sourceLanguage'  => $task->get_source_language(),
				'targetLanguages' => serialize( $task->get_target_languages() ),
				'status'		  => $task->get_status(),
				'projectID'	      => $task->get_project_id(),
				'profileID'       => intval( $task->get_profile_id() ),
			];

			if ( $wpdb->update( $table_name, $data, [ 'id' => $task->get_id() ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $persists
	 *
	 * @return mixed|void
	 */
	protected function do_flush( array $persists ) {
		/* @var Task[] $persists */
		foreach ( $persists as $task ) {
			if ( get_class( $task ) === 'SmartCAT\WP\DB\Entity\Task' ) {
				if ( empty( $task->get_id() ) ) {
					if ( $res = $this->add( $task ) ) {
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
		$result = new Task();

		if ( isset( $row->id ) ) {
			$result->set_id( intval( $row->id ) );
		}

		if ( isset( $row->sourceLanguage ) ) {
			$result->set_source_language( $row->sourceLanguage );
		}

		if ( isset( $row->targetLanguages ) ) {
			$result->set_target_languages( unserialize( $row->targetLanguages ) );
		}

		if ( isset( $row->status ) ) {
			$result->set_status( $row->status );
		}

		if ( isset( $row->projectID ) ) {
			$result->set_project_id( $row->projectID );
		}

		if ( isset( $row->profileID ) ) {
			$result->set_profile_id( $row->profileID );
		}

		return $result;
	}

	/**
	 * @param Task $task
	 *
	 * @return mixed|void
	 */
	public function save( $task ) {
		if ( $task->get_id() ) {
			$this->update( $task );
		} else {
			$this->add( $task );
		}
	}
}
