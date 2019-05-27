<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\DB\Repository;

use SmartCAT\WP\DB\Entity\Task;

/** Репозиторий таблицы обмена */
class TaskRepository extends RepositoryAbstract
{
    const TABLE_NAME = 'tasks';

    public function get_table_name()
    {
        return $this->prefix . self::TABLE_NAME;
    }

    /**
     * @return Task[]
     */
    public function get_new_task()
    {
        return $this->get_tasks_by_status(Task::STATUS_NEW);
    }

    /**
     * @param $status string|array
     * @return Task[]
     */
    public function get_tasks_by_status($status)
    {
        $table_name = $this->get_table_name();

        if (is_array($status)) {
            $status = implode("', and status='", $status);
        }

        $query = sprintf(
            "SELECT * FROM %s WHERE status='%s'",
            $table_name,
            $status
        );

        $results = $this->get_wp_db()->get_results($query);

        return $this->prepare_result($results);
    }

    /**
     * @param $post_id int
     *
     * @return bool
     */
    public function task_new_post_id_exists($post_id)
    {
        $table_name = $this->get_table_name();

        $query = sprintf(
            "SELECT * FROM %s WHERE status='%s' AND postID=%d",
            $table_name,
            Task::STATUS_NEW,
            $post_id
       );

        $results = $this->get_wp_db()->get_results($query);

        return (count($this->prepare_result($results)) > 0);
    }

    public function add(Task $task)
    {
        $table_name = $this->get_table_name();
        $wpdb       = $this->get_wp_db();

        $data = [
            'sourceLanguage'  => $task->get_source_language(),
            'targetLanguages' => serialize($task->get_target_languages()),
            'status'          => $task->get_status(),
            'projectID'       => $task->get_project_d(),
            'postID'          => $task->get_post_id()
        ];

        if (! empty($task->get_id())) {
            $data['id'] = $task->get_id();
        }

        //TODO: м.б. заменить на try-catch

        if ($wpdb->insert($table_name, $data)) {
            $task->set_id($wpdb->insert_id);
            return $wpdb->insert_id;
        }

        return false;
    }

    public function update(Task $task)
    {
        $table_name = $this->get_table_name();
        $wpdb       = $this->get_wp_db();

        if (! empty($task->get_id())) {
            $data = [
                'sourceLanguage'  => $task->get_source_language(),
                'targetLanguages' => serialize($task->get_target_languages()),
                'status'          => $task->get_status(),
                'projectID'       => $task->get_project_d(),
                'postID'          => $task->get_post_id()
            ];
            //TODO: м.б. заменить на try-catch
            if ($wpdb->update($table_name, $data, [ 'id' => $task->get_id() ])) {
                return true;
            }
        }

        return false;
    }

    protected function do_flush(array $persists)
    {
        /* @var Task[] $persists */
        foreach ($persists as $task) {
            if (get_class($task) === 'SmartCAT\WP\DB\Entity\Task') {
                if (empty($task->get_id())) {
                    if ($res = $this->add($task)) {
                        $task->set_id($res);
                    }
                } else {
                    $this->update($task);
                }
            }
        }
    }

    protected function to_entity($row)
    {
        $result = new Task();

        if (isset($row->id)) {
            $result->set_id(intval($row->id));
        }

        if (isset($row->sourceLanguage)) {
            $result->set_source_language($row->sourceLanguage);
        }

        if (isset($row->targetLanguages)) {
            $result->set_target_languages(unserialize($row->targetLanguages));
        }

        if (isset($row->postID)) {
            $result->set_post_id(intval($row->postID));
        }

        if (isset($row->status)) {
            $result->set_status($row->status);
        }

        if (isset($row->projectID)) {
            $result->set_project_id($row->projectID);
        }

        return $result;
    }

    /**
     * @param array $criterias
     *
     * @return Task|null
     */
    public function get_one_by_id($id)
    {
        $table_name = $this->get_table_name();
        $wpdb       = $this->get_wp_db();

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id=%d", $id));

        return $row ? $this->to_entity($row) : null;
    }
}
