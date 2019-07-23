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

use Psr\Container\ContainerInterface;
use SmartCat\Client\Model\DocumentModel;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Entity\Task;
use SmartCAT\WP\Helpers\Language\LanguageConverter;

/**
 * Class StatisticRepository
 *
 * @method Statistics get_one_by_id( int $id )
 * @method Statistics[] get_all_by( array $criterias )
 * @method Statistics get_one_by( array $criterias )
 * @method Statistics[] get_all( $from = 0, $limit = 0 )
 *
 * @package SmartCAT\WP\DB\Repository
 */
class StatisticRepository extends RepositoryAbstract {
	const TABLE_NAME = 'statistic';

	/**
	 * @return mixed|string
	 */
	public function get_table_name() {
		return $this->prefix . self::TABLE_NAME;
	}

	/**
	 * Возращает список постов ожидающих перевода
	 *
	 * @param array $documents = [] - если передан параметр то из списка исключаются все докумениты не попавшие в массив
	 *
	 * @return Statistics[]
	 */
	public function get_sended( array $documents = [] ) {
		return $this->get_by_status( Statistics::STATUS_SENDED, $documents );
	}

	/**
	 * Возращает список постов ожидающих экспорта
	 *
	 * @param array $documents = [] - если передан параметр то из списка исключаются все докумениты не попавшие в массив
	 *
	 * @return Statistics[]
	 */
	public function get_export( array $documents = [] ) {
		return $this->get_by_status( Statistics::STATUS_EXPORT, $documents );
	}

	/**
	 * Возращает список новых постов
	 *
	 * @param array $documents = [] - если передан параметр то из списка исключаются все докумениты не попавшие в массив
	 *
	 * @return Statistics[]
	 */
	public function get_new( array $documents = [] ) {
		return $this->get_by_status( Statistics::STATUS_NEW, $documents );
	}

	/**
	 * @param $status string|array
	 * @param array $documents
	 * @return Statistics[]
	 */
	public function get_by_status( $status, array $documents = [] ) {
		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();

		if ( is_array( $status ) ) {
			$status = implode( "', OR status='", $status );
		}

		$query = "SELECT * FROM $table_name WHERE status='$status'";

		$documents_count = count( $documents );
		if ( $documents_count > 0 ) {
			$ids             = array_fill( 0, $documents_count, '%s' );
			$documents_where = 'AND documentID in ( ' . implode( ',', $ids ) . ' )';
			$query           = $wpdb->prepare( "$query $documents_where", $documents );
		}

		$results = $wpdb->get_results( $query );

		return $this->prepare_result( $results );
	}

	/**
	 * @param $post_id
	 *
	 * @return bool
	 */
	public function delete_by_post_id( $post_id ) {
		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();

		if ( ! is_null( $post_id ) && ! empty( $post_id ) && is_int( $post_id ) ) {
			if ( $wpdb->delete( $table_name, [ 'postID' => $post_id ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Statistics $stat
	 *
	 * @return bool
	 */
	public function delete( Statistics $stat ) {
		return $this->delete_by_id( $stat->get_id() );
	}

	/**
	 * @param $row
	 *
	 * @return mixed|Statistics
	 */
	protected function to_entity( $row ) {
		return new Statistics( $row );
	}

	/**
	 * @param Statistics[] $persists
	 *
	 * @return mixed|void
	 */
	protected function do_flush( array $persists ) {
		foreach ( $persists as $stat ) {
			if ( $stat instanceof Statistics ) {
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

	/**
	 * @param Task $task
	 * @param DocumentModel[]|DocumentModel $document
	 * @return false|int
	 * @throws \SmartCAT\WP\Helpers\Language\Exceptions\LanguageNotFoundException
	 */
	public function link_to_smartcat_document( Task $task, $document ) {
		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var LanguageConverter $converter */
		$converter = $container->get( 'language.converter' );

		if ( is_array( $document ) ) {
			foreach ( $document as $document_model ) {
				if ( $document_model instanceof DocumentModel ) {
					$this->link_to_smartcat_document( $task, $document_model );
				}
			}

			return true;
		}

		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();
		$data       = [
			'documentID' => $document->getId(),
			'status'     => Statistics::STATUS_SENDED,
		];

		return $wpdb->update(
			$table_name,
			$data,
			[
				'taskId'         => $task->get_id(),
				'targetLanguage' => $converter->get_wp_code_by_sc( $document->getTargetLanguage() )->get_wp_code(),
			]
		);
	}
}
