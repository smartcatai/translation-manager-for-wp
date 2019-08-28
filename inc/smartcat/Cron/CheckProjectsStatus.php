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

namespace SmartCAT\WP\Cron;

use Http\Client\Common\Exception\ClientErrorException;
use Psr\Container\ContainerInterface;
use SmartCat\Client\Model\ProjectModel;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\DB\Repository\TaskRepository;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\ProjectManager;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\WP\Options;

/**
 * Class CheckProjectsStatus
 *
 * @package SmartCAT\WP\Cron
 */
class CheckProjectsStatus extends CronAbstract {

	/** @var SmartCAT $smartcat */
	private $smartcat;
	/** @var StatisticRepository $statistic_repository */
	private $statistic_repository;
	/** @var TaskRepository $task_repository */
	private $task_repository;
	/** @var Options */
	private $options;

	/**
	 * @return mixed
	 */
	public function get_interval() {
		$schedules['1m'] = [
			'interval' => 60,
			'display'  => __( 'Every minute', 'translation-connectors' ),
		];

		return $schedules;
	}

	/**
	 *
	 */
	public function run() {
		if ( ! SmartCAT::is_active() ) {
			return;
		}

		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		$this->smartcat             = $container->get( 'smartcat' );
		$this->options              = $container->get( 'core.options' );
		$this->statistic_repository = $container->get( 'entity.repository.statistic' );
		$this->task_repository      = $container->get( 'entity.repository.task' );

		$this->options->set( 'last_cron_check', time() );

		Logger::event( 'cron', 'Checking documents started' );

		$statistics = $this->statistic_repository->get_by_status( [ Statistics::STATUS_SENDED, Statistics::STATUS_EXPORT ] );
		$count      = count( $statistics );

		Logger::event( 'cron', "Find $count documents to check" );

		try {
			foreach ( $statistics as $statistic ) {
				$task    = $this->task_repository->get_one_by_id( $statistic->get_task_id() );
				$project = $this->smartcat->getProjectManager()->projectGet( $task->get_project_id() );

				if ( $this->canceled_check( $statistic, $project ) ) {
					continue;
				}

				if ( $statistic->get_status() === Statistics::STATUS_EXPORT ) {
					$this->create_post( $statistic );
					continue;
				}

				if ( $statistic->get_status() === Statistics::STATUS_SENDED ) {
					$document = $this->smartcat->getDocumentManager()->documentGet(
						[ 'documentId' => $statistic->get_document_id() ]
					);

					$stages   = $document->getWorkflowStages();
					$progress = 0;

					foreach ( $stages as $stage ) {
						$progress += $stage->getProgress();
					}

					$progress = round( $progress / count( $stages ), 2 );
					$statistic->set_progress( $progress );
					$this->statistic_repository->save( $statistic );

					if ( $document->getStatus() === 'completed' ) {
						$this->export_request( $statistic );
					}
				}
			}
		} catch (\Exception $e) {
			Logger::error(
				"Document {$statistic->get_document_id()}, main error",
				"Message: {$e->getMessage()}"
			);
		}

		Logger::event( 'cron', 'Checking documents ended' );
	}

	/**
	 * @param Statistics $statistic
	 * @param ProjectModel $project
	 *
	 * @return bool
	 */
	private function canceled_check( $statistic, $project ) {
		if ( $project->getStatus() === 'canceled' ) {
			$stat_update = $this->statistic_repository->get_all_by( [ 'taskId' => $statistic->get_task_id() ] );

			foreach ( $stat_update as $stat ) {
				$stat->set_status( Statistics::STATUS_CANCELED );
				$this->statistic_repository->save( $stat );
			}

			return true;
		}

		return false;
	}

	/**
	 * @param Statistics $statistic
	 *
	 * @return void
	 */
	private function export_request( $statistic ) {
		try {
			Logger::event( 'exporting', "Export request '{$statistic->get_document_id()}'" );

			$task = $this->smartcat->getDocumentExportManager()
			                       ->documentExportRequestExport( [ 'documentIds' => [ $statistic->get_document_id() ] ] );
			if ( $task->getId() ) {
				$statistic->set_export_id( $task->getId() );
				$statistic->set_status( Statistics::STATUS_EXPORT );
				$this->statistic_repository->update( $statistic );
			}

			Logger::event( 'exporting', "Export request '{$statistic->get_document_id()}' done" );
		} catch ( ClientErrorException $e ) {
			if ( $e->getResponse()->getStatusCode() === 404 ) {
				$this->statistic_repository->delete( $statistic );
				Logger::event( 'exporting', "Deleted '{$statistic->get_document_id()}'" );
			} else {
				Logger::error(
					"Document {$statistic->get_document_id()}, request download",
					"API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}"
				);
			}
		} catch ( \Throwable $e ) {
			Logger::error(
				"Document {$statistic->get_document_id()}, request download",
				"Message: {$e->getMessage()}"
			);
		}
	}

	/**
	 * @param Statistics $statistic
	 *
	 * @return void
	 */
	private function create_post( $statistic ) {
		Logger::event( 'createPost', "Start create post '{$statistic->get_document_id()}'" );

		try {
			$result = $this->smartcat->getDocumentExportManager()->documentExportDownloadExportResult( $statistic->get_export_id() );
			if ( 204 === $result->getStatusCode() ) {
				Logger::event( 'createPost', "Export not done yet '{$statistic->get_document_id()}'" );
			} elseif ( 200 === $result->getStatusCode() ) {
				Logger::event( 'createPost', "Download document '{$statistic->get_document_id()}'" );
				ProjectManager::publish( $statistic, $result->getBody()->getContents() );
				$statistic->set_status( Statistics::STATUS_COMPLETED )->set_export_id( null );
				Logger::event( 'createPost', "Generated post for '{$statistic->get_document_id()}' and status = {$statistic->get_status()}" );
			}
		} catch ( ClientErrorException $e ) {
			if ( 404 === $e->getResponse()->getStatusCode() ) {
				$statistic->set_status( Statistics::STATUS_SENDED );
			} else {
				$statistic->set_status( Statistics::STATUS_FAILED );
			}
			Logger::error(
				"Document {$statistic->get_document_id()}, download translate error",
				"API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}"
			);
		} catch ( \Throwable $e ) {
			$statistic->set_status( Statistics::STATUS_SENDED );
			Logger::error( "Document {$statistic->get_document_id()}, download translate error", "Message: {$e->getMessage()}" );
		} finally {
			$this->statistic_repository->save( $statistic );
		}

		Logger::event( 'createPost', "End create post '{$statistic->get_document_id()}'" );
	}
}
