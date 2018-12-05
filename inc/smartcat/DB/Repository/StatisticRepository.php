<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 16.06.2017
 * Time: 18:48
 */

namespace SmartCAT\WP\DB\Repository;

use Psr\Container\ContainerInterface;
use SmartCAT\API\Model\DocumentModel;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Entity\Task;
use SmartCAT\WP\Helpers\Language\LanguageConverter;


/** Репозиторий таблицы статистики */
class StatisticRepository extends RepositoryAbstract {
	const TABLE_NAME = 'statistic';

	public function get_table_name() {
		return $this->prefix . self::TABLE_NAME;
	}

	public function install() {
		$table_name = $this->get_table_name();
		$sql        = "
			CREATE TABLE IF NOT EXISTS {$table_name} (
				id  BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				taskId BIGINT(20) UNSIGNED NOT NULL,
				postID BIGINT(20) UNSIGNED NOT NULL,
				sourceLanguage VARCHAR(255) NOT NULL,
				targetLanguage VARCHAR(255) NOT NULL,
				progress DECIMAL(10,2) NOT NULL DEFAULT '0',
				wordsCount BIGINT(20) UNSIGNED,
				targetPostID BIGINT(20) UNSIGNED,
				documentID VARCHAR(255),
				status VARCHAR(20) NOT NULL DEFAULT 'new',
				errorCount BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY  (id),
				INDEX status (`status`),
				INDEX documentID (`documentID`)
			) ROW_FORMAT=DYNAMIC ";
		$this->create_table( $sql );
	}

	public function uninstall() {
		$table_name = $this->get_table_name();
		$this->drop_table( $table_name );
	}

	/**
	 * @param int $from
	 * @param int $limit
	 *
	 * @return Statistics[]
	 */
	public function get_statistics( $from = 0, $limit = 100 ) {
		$wpdb = $this->get_wp_db();
		$from = intval( $from );
		$from >= 0 || $from = 0;
		$limit = intval( $limit );

		$table_name = $this->get_table_name();
		$results    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name LIMIT %d, %d",
			[ $from, $limit ] ) );

		return $this->prepare_result( $results );
	}

	/**
	 * Возращает список постов ожидающих перевода
	 *
	 * @param array $documents = [] - если передан параметр то из списка исключаются все докумениты не попавшие в массив
	 *
	 * @return Statistics[]
	 */
	public function get_sended( array $documents = [] ) {
		return $this->get_by_status( 'sended',  $documents );
	}

	/**
	 * Возращает список постов ожидающих экспорта
	 *
	 * @param array $documents = [] - если передан параметр то из списка исключаются все докумениты не попавшие в массив
	 *
	 * @return Statistics[]
	 */
	public function get_export( array $documents = [] ) {
		return $this->get_by_status( 'export',  $documents );
	}

	/**
	 * Возращает список новых постов
	 *
	 * @param array $documents = [] - если передан параметр то из списка исключаются все докумениты не попавшие в массив
	 *
	 * @return Statistics[]
	 */
	public function get_new( array $documents = [] ) {
		return $this->get_by_status( 'new',  $documents );
	}

    /**
     * @param $status
     * @param array $documents
     * @return array
     */
    private function get_by_status($status, array $documents = [] ) {
		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();

		$query = /** @lang MySQL */
            "SELECT * FROM $table_name WHERE status='$status'";

		$documents_count = count( $documents );
		if ( $documents_count > 0 ) {
			$ids             = array_fill( 0, $documents_count, '%s' );
			$documents_where = 'AND documentID in (' . implode( ',', $ids ) . ')';
			$query           = $wpdb->prepare( "$query $documents_where", $documents );
		}

		$results = $wpdb->get_results( $query );

		return $this->prepare_result( $results );
	}

	public function add( Statistics $stat ) {
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

		if ( ! empty( $stat->get_id() ) ) {
			$data['id'] = $stat->get_id();
		}

		//TODO: м.б. заменить на try-catch

		if ( $wpdb->insert( $table_name, $data ) ) {
			$stat->set_id($wpdb->insert_id);
			return $wpdb->insert_id;
		}

		return false;
	}

	public function update( Statistics $stat ) {
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

		if ( ! empty( $stat->get_id() ) ) {
			//TODO: м.б. заменить на try-catch

			if ( $wpdb->update( $table_name, $data, [ 'id' => $stat->get_id() ] ) ) {
				return true;
			}
		}

		return false;
	}

	public function delete_by_post_id( $post_id ) {
		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();

		if ( ! is_null( $post_id ) && ! empty( $post_id ) && is_int( $post_id ) ) {
			//TODO: м.б. заменить на try-catch
			if ( $wpdb->delete( $table_name, [ 'postID' => $post_id ] ) ) {
				return true;
			}
		}

		return false;
	}

	public function delete( Statistics $stat ) {
		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();

		if ( ! empty( $stat->get_id() ) ) {
			//TODO: м.б. заменить на try-catch
			if ( $wpdb->delete( $table_name, [ 'id' => $stat->get_id() ] ) ) {
				return true;
			}
		}

		return false;
	}

	protected function to_entity( $row ) {
		$result = new Statistics();

		if ( isset( $row->id ) ) {
			$result->set_id( $row->id );
		}

		if ( isset( $row->taskId ) ) {
			$result->set_task_id( $row->taskId );
		}

		if ( isset( $row->postID ) ) {
			$result->set_post_id( $row->postID );
		}

		if ( isset( $row->sourceLanguage ) ) {
			$result->set_source_language( $row->sourceLanguage );
		}

		if ( isset( $row->targetLanguage ) ) {
			$result->set_target_language( $row->targetLanguage );
		}

		if ( isset( $row->progress ) ) {
			$result->set_progress( $row->progress );
		}

		if ( isset( $row->wordsCount ) ) {
			$result->set_words_count( $row->wordsCount );
		}

		if ( isset( $row->targetPostID ) ) {
			$result->set_target_post_id( $row->targetPostID );
		}

		if ( isset( $row->documentID ) ) {
			$result->set_document_id( $row->documentID );
		}

		if ( isset( $row->status ) ) {
			$result->set_status( $row->status );
		}

		if ( isset( $row->errorCount ) ) {
			$result->set_error_count( $row->errorCount );
		}

		return $result;
	}

	protected function do_flush( array $persists ) {
		/* @var Statistics[] $persists */
		foreach ( $persists as $stat ) {
			if ( get_class( $stat ) === 'SmartCAT\WP\DB\Entity\Statistics' ) {
				if ( empty( $stat->get_id() ) ) {
					if ( $res = $this->add( $stat ) ) {
						$stat->set_id( $res );
					}
				} else {
					$this->update( $stat );
				}
			}
		}
	}

	public function link_to_smartcat_document( Task $task, DocumentModel $document ) {
		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var LanguageConverter $converter */
		$converter = $container->get( 'language.converter' );

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
				'targetLanguage' => $converter->get_wp_code_by_sc( $document->getTargetLanguage() )->get_wp_code()
			]
		);
	}

	/**
	 * @param array $criterias
	 *
	 * @return Statistics|null
	 */
	public function get_one_by( array $criterias ) {
		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();
		$query      = "SELECT * FROM $table_name WHERE ";

		$where = $values = [];

		foreach ( $criterias as $key => $value ) {
			$where[]  = "$key=%s";
			$values[] = $value;
		}

		$row = $wpdb->get_row( $wpdb->prepare( $query . implode( " AND ", $where ), $values ) );

		return $row ? $this->to_entity( $row ) : null;
	}

}