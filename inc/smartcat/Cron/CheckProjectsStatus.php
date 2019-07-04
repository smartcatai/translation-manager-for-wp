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

use Psr\Container\ContainerInterface;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\DB\Repository\TaskRepository;
use SmartCAT\WP\Helpers\SmartCAT;

/**
 * Class CheckProjectsStatus
 *
 * @package SmartCAT\WP\Cron
 */
class CheckProjectsStatus extends CronAbstract {
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

		SmartCAT::debug( 'Checking documents started' );

		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var StatisticRepository $statistic_repository */
		$statistic_repository = $container->get( 'entity.repository.statistic' );

		/** @var TaskRepository $task_repository */
		$task_repository = $container->get( 'entity.repository.task' );

		/** @var SmartCAT $smartcat */
		$smartcat = $container->get( 'smartcat' );

		$statistics = $statistic_repository->get_sended();
		$count      = count( $statistics );

		SmartCAT::debug( "Finded $count documents to check" );

		foreach ( $statistics as $statistic ) {
			if ( $statistic->get_status() !== Statistics::STATUS_SENDED ) {
				continue;
			}

			$task    = $task_repository->get_one_by_id( $statistic->get_task_id() );
			$project = $smartcat->getProjectManager()->projectGet( $task->get_project_id() );

			if ( $project->getStatus() === 'canceled' ) {
				$stat_update = $statistic_repository->get_all_by( [ 'taskId' => $statistic->get_task_id() ] );

				foreach ( $stat_update as $stat ) {
					$stat->set_status( Statistics::STATUS_CANCELED );
					$statistic_repository->save( $stat );
				}

				continue;
			}

			$document = $smartcat->getDocumentManager()->documentGet(
				[ 'documentId' => $statistic->get_document_id() ]
			);

			$stages   = $document->getWorkflowStages();
			$progress = 0;

			foreach ( $stages as $stage ) {
				$progress += $stage->getProgress();
			}

			$progress = round( $progress / count( $stages ), 2 );
			$statistic->set_progress( $progress );

			$statistic_repository->save( $statistic );
		}

		SmartCAT::debug( 'Checking documents ended' );
	}
}
