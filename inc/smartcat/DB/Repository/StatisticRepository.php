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

use Psr\Container\ContainerInterface;
use SmartCat\Client\Model\DocumentModel;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Entity\Task;
use SmartCAT\WP\Helpers\Language\LanguageConverter;

/** Репозиторий таблицы статистики */
class StatisticRepository extends RepositoryAbstract
{
    const TABLE_NAME = 'statistic';

    public function get_table_name()
    {
        return $this->prefix . self::TABLE_NAME;
    }

    /**
     * @param int $from
     * @param int $limit
     *
     * @return Statistics[]
     */
    public function get_statistics($from = 0, $limit = 100)
    {
        $wpdb = $this->get_wp_db();
        $from = intval($from);
        $from >= 0 || $from = 0;
        $limit = intval($limit);

        $table_name = $this->get_table_name();
        $results = $wpdb->get_results(
            $wpdb->prepare(
            "SELECT * FROM $table_name LIMIT %d, %d",
                [$from, $limit]
            )
        );

        return $this->prepare_result($results);
    }

    /**
     * Возращает список постов ожидающих перевода
     *
     * @param array $documents = [] - если передан параметр то из списка исключаются все докумениты не попавшие в массив
     *
     * @return Statistics[]
     */
    public function get_sended(array $documents = [])
    {
        return $this->get_by_status('sended',  $documents);
    }

    /**
     * Возращает список постов ожидающих экспорта
     *
     * @param array $documents = [] - если передан параметр то из списка исключаются все докумениты не попавшие в массив
     *
     * @return Statistics[]
     */
    public function get_export(array $documents = [])
    {
        return $this->get_by_status('export',  $documents);
    }

    /**
     * Возращает список новых постов
     *
     * @param array $documents = [] - если передан параметр то из списка исключаются все докумениты не попавшие в массив
     *
     * @return Statistics[]
     */
    public function get_new(array $documents = [])
    {
        return $this->get_by_status('new',  $documents);
    }

    /**
     * @param $status
     * @param array $documents
     * @return array
     */
    private function get_by_status($status, array $documents = [])
    {
        $table_name = $this->get_table_name();
        $wpdb       = $this->get_wp_db();

        $query = /** @lang MySQL */
            "SELECT * FROM $table_name WHERE status='$status'";

        $documents_count = count($documents);
        if ($documents_count > 0) {
            $ids             = array_fill(0, $documents_count, '%s');
            $documents_where = 'AND documentID in (' . implode(',', $ids) . ')';
            $query           = $wpdb->prepare("$query $documents_where", $documents);
        }

        $results = $wpdb->get_results($query);

        return $this->prepare_result($results);
    }

    public function add(Statistics $stat)
    {
        $table_name = $this->get_table_name();
        $wpdb       = $this->get_wp_db();

        $data = [
            'taskId'         => $stat->get_task_id(),
            'postID'         => $stat->get_post_id(),
            'sourceLanguage' => $stat->get_source_language(),
            'targetLanguage' => $stat->get_target_language(),
            'progress'       => $stat->get_progress(),
            'wordsCount'     => $stat->get_words_count(),
            'targetPostID'   => $stat->get_target_post_id(),
            'documentID'     => $stat->get_document_id(),
            'status'         => $stat->get_status(),
            'errorCount'     => $stat->get_error_count(),
        ];

        if (! empty($stat->get_id())) {
            $data['id'] = $stat->get_id();
        }

        //TODO: м.б. заменить на try-catch

        if ($wpdb->insert($table_name, $data)) {
            $stat->set_id($wpdb->insert_id);
            return $wpdb->insert_id;
        }

        return false;
    }

    public function update(Statistics $stat)
    {
        $table_name = $this->get_table_name();
        $wpdb       = $this->get_wp_db();

        $data = [
            'taskId'         => $stat->get_task_id(),
            'postID'         => $stat->get_post_id(),
            'sourceLanguage' => $stat->get_source_language(),
            'targetLanguage' => $stat->get_target_language(),
            'progress'       => $stat->get_progress(),
            'wordsCount'     => $stat->get_words_count(),
            'targetPostID'   => $stat->get_target_post_id(),
            'documentID'     => $stat->get_document_id(),
            'status'         => $stat->get_status(),
            'errorCount'     => $stat->get_error_count(),
        ];

        if (! empty($stat->get_id())) {
            //TODO: м.б. заменить на try-catch

            if ($wpdb->update($table_name, $data, [ 'id' => $stat->get_id() ])) {
                return true;
            }
        }

        return false;
    }

    public function delete_by_post_id($post_id)
    {
        $table_name = $this->get_table_name();
        $wpdb       = $this->get_wp_db();

        if (! is_null($post_id) && ! empty($post_id) && is_int($post_id)) {
            //TODO: м.б. заменить на try-catch
            if ($wpdb->delete($table_name, [ 'postID' => $post_id ])) {
                return true;
            }
        }

        return false;
    }

    public function mark_failed_by_task_id($task_id)
    {
        $table_name = $this->get_table_name();
        $wpdb       = $this->get_wp_db();

        if (! is_null($task_id) && ! empty($task_id) && is_int($task_id)) {
            if ($wpdb->update($table_name, ['status' => 'failed'], [ 'taskId' => $task_id ])) {
                return true;
            }
        }

        return false;
    }

    public function delete(Statistics $stat)
    {
        $table_name = $this->get_table_name();
        $wpdb       = $this->get_wp_db();

        if (! empty($stat->get_id())) {
            //TODO: м.б. заменить на try-catch
            if ($wpdb->delete($table_name, [ 'id' => $stat->get_id() ])) {
                return true;
            }
        }

        return false;
    }

    protected function to_entity($row)
    {
        $result = new Statistics();

        if (isset($row->id)) {
            $result->set_id($row->id);
        }

        if (isset($row->taskId)) {
            $result->set_task_id($row->taskId);
        }

        if (isset($row->postID)) {
            $result->set_post_id($row->postID);
        }

        if (isset($row->sourceLanguage)) {
            $result->set_source_language($row->sourceLanguage);
        }

        if (isset($row->targetLanguage)) {
            $result->set_target_language($row->targetLanguage);
        }

        if (isset($row->progress)) {
            $result->set_progress($row->progress);
        }

        if (isset($row->wordsCount)) {
            $result->set_words_count($row->wordsCount);
        }

        if (isset($row->targetPostID)) {
            $result->set_target_post_id($row->targetPostID);
        }

        if (isset($row->documentID)) {
            $result->set_document_id($row->documentID);
        }

        if (isset($row->status)) {
            $result->set_status($row->status);
        }

        if (isset($row->errorCount)) {
            $result->set_error_count($row->errorCount);
        }

        return $result;
    }

    protected function do_flush(array $persists)
    {
        /* @var Statistics[] $persists */
        foreach ($persists as $stat) {
            if (get_class($stat) === 'SmartCAT\WP\DB\Entity\Statistics') {
                if (empty($stat->get_id())) {
                    if ($res = $this->add($stat)) {
                        $stat->set_id($res);
                    }
                } else {
                    $this->update($stat);
                }
            }
        }
    }

    /**
     * @param Task $task
     * @param DocumentModel[]|DocumentModel $document
     * @return false|int
     * @throws \SmartCAT\WP\Helpers\Language\Exceptions\LanguageNotFoundException
     */
    public function link_to_smartcat_document(Task $task, $document)
    {
        /** @var ContainerInterface $container */
        $container = Connector::get_container();

        /** @var LanguageConverter $converter */
        $converter = $container->get('language.converter');

        if (is_array($document)) {
            foreach ($document as $singleDocument) {
                if ($singleDocument instanceof DocumentModel) {
                    $this->link_to_smartcat_document($task, $singleDocument);
                }
            }
        }

        $table_name = $this->get_table_name();
        $wpdb       = $this->get_wp_db();
        $data       = [
            'documentID' => $document->getId(),
            'status'     => 'sended'
        ];

        return $wpdb->update(
            $table_name,
            $data,
            [
                'taskId'         => $task->get_id(),
                'targetLanguage' => $converter->get_wp_code_by_sc($document->getTargetLanguage())->get_wp_code()
            ]
        );
    }

    /**
     * @param array $criterias
     *
     * @return Statistics|null
     */
    public function get_one_by(array $criterias)
    {
        $table_name = $this->get_table_name();
        $wpdb       = $this->get_wp_db();
        $query      = "SELECT * FROM $table_name WHERE ";

        $where = $values = [];

        foreach ($criterias as $key => $value) {
            $where[]  = "$key=%s";
            $values[] = $value;
        }

        $row = $wpdb->get_row($wpdb->prepare($query . implode(" AND ", $where), $values));

        return $row ? $this->to_entity($row) : null;
    }
}
