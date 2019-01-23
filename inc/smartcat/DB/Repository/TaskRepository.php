<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 16.06.2017
 * Time: 18:48
 */

namespace SmartCAT\WP\DB\Repository;

use SmartCAT\WP\DB\Entity\Task;


/** Репозиторий таблицы обмена */
class TaskRepository extends RepositoryAbstract {
	const TABLE_NAME = 'tasks';

	public function get_table_name() {
		return $this->prefix . self::TABLE_NAME;
	}

	public function install() {
		$table_name = $this->get_table_name();
		$sql        = "
			CREATE TABLE IF NOT EXISTS {$table_name} (
				id  BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				sourceLanguage VARCHAR(255) NOT NULL,
				targetLanguages TEXT NOT NULL,
				postID BIGINT(20) UNSIGNED NOT NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'new',
				projectID VARCHAR(255),
				PRIMARY KEY  (id),
				INDEX status (`status`)
			)";
		$this->create_table( $sql );
	}

	public function uninstall() {
		$table_name = $this->get_table_name();
		$this->drop_table( $table_name );
	}

	/**
	 * @return Task[]
	 */
	public function get_new_task() {
		$table_name = $this->get_table_name();
		$results    = $this->get_wp_db()->get_results( "SELECT * FROM $table_name WHERE status='new'" );

		return $this->prepare_result( $results );
	}

	/**
	 * @param $post_id int
	 *
	 * @return bool
	 */
	public function task_new_post_id_exists($post_id) {
		$table_name = $this->get_table_name();
		$results = $this->get_wp_db()->get_results("SELECT * FROM $table_name WHERE status='new' AND postID=$post_id");

		return (count($this->prepare_result( $results )) > 0);
	}

	public function add( Task $task ) {
		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();

		$data = [
			'sourceLanguage'  => $task->get_source_language(),
			'targetLanguages' => serialize( $task->get_target_languages() ),
			'status'          => $task->get_status(),
			'projectID'       => $task->get_project_d(),
			'postID'          => $task->get_post_id()
		];

		if ( ! empty( $task->get_id() ) ) {
			$data['id'] = $task->get_id();
		}

		//TODO: м.б. заменить на try-catch

		if ( $wpdb->insert( $table_name, $data ) ) {
			$task->set_id($wpdb->insert_id);
			return $wpdb->insert_id;
		}

		return false;
	}

	public function update( Task $task ) {
		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();

		if ( ! empty( $task->get_id() ) ) {
			$data = [
				'sourceLanguage'  => $task->get_source_language(),
				'targetLanguages' => serialize( $task->get_target_languages() ),
				'status'          => $task->get_status(),
				'projectID'       => $task->get_project_d(),
				'postID'          => $task->get_post_id()
			];
			//TODO: м.б. заменить на try-catch
			if ( $wpdb->update( $table_name, $data, [ 'id' => $task->get_id() ] ) ) {
				return true;
			}
		}

		return false;
	}

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

		if ( isset( $row->postID ) ) {
			$result->set_post_id( intval( $row->postID ) );
		}

		if ( isset( $row->status ) ) {
			$result->set_status( $row->status );
		}

		if ( isset( $row->projectID ) ) {
			$result->set_project_id( $row->projectID );
		}

		return $result;
	}

    /**
     * @param array $criterias
     *
     * @return Task|null
     */
    public function get_one_by_id( $id ) {
        $table_name = $this->get_table_name();
        $wpdb       = $this->get_wp_db();

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id=%d", $id ) );

        return $row ? $this->to_entity( $row ) : null;
    }
}