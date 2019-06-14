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
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Task;
use SmartCAT\WP\DB\Repository\ProfileRepository;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\DB\Repository\TaskRepository;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\Helpers\Utils;

/**
 * Class SendToSmartCAT
 *
 * @package SmartCAT\WP\Cron
 */
class SendToSmartCAT extends CronAbstract {
	/**
	 * @return mixed
	 */
	public function get_interval()
	{
		$schedules['1m'] = [
			'interval' => 60,
			'display' => __( 'Every minute', 'translation-connectors' ),
		];

		return $schedules;
	}

	/**
	 * Main function cron run
	 */
	public function run() {
		if ( ! SmartCAT::is_active() ) {
			return;
		}

		SmartCAT::debug( 'Sending to SmartCat started' );

		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var TaskRepository $task_repository */
		$task_repository = $container->get( 'entity.repository.task' );

		/** @var StatisticRepository $statistic_repository */
		$statistic_repository = $container->get( 'entity.repository.statistic' );

		/** @var ProfileRepository $profile_repository */
		$profile_repository = $container->get( 'entity.repository.profile' );

		/** @var Utils $utils */
		$utils = $container->get( 'utils' );

		/** @var SmartCAT $smartcat */
		$smartcat = $container->get( 'smartcat' );

		/** @var Statistics[] $statistics */
		$statistics = $statistic_repository->get_by_status( Statistics::STATUS_NEW );

		$count = count( $statistics );
		SmartCAT::debug( "Finded $count tasks to send" );

		foreach ( $statistics as $statistic ) {
			$task      = $task_repository->get_one_by_id( $statistic->get_task_id() );
			$profile   = $profile_repository->get_one_by_id( $task->get_profile_id() );
			$file      = $utils->get_post_to_file( $statistic->get_post_id() );
			$task_name = $smartcat::get_task_name_from_stream( $file );

			try {
				if ( ! empty( $task->get_project_id() ) ) {
					SmartCAT::debug( "Sending '{$task_name}'" );

					$project_id     = $task->get_project_id();
					$document_model = $smartcat->create_document( $file );
					$document       = $smartcat->update_project( $document_model, $task );

					$statistic_repository->link_to_smartcat_document( $task, $document );
					SmartCAT::debug( "Sended '{$task_name}'" );
				} else {
					SmartCAT::debug( "Creating '{$task_name}'" );

					$smartcat_project = $smartcat->create_project( $file, $profile );
					$project_id       = $smartcat_project->getId();

					$statistic_repository->link_to_smartcat_document( $task, $smartcat_project->getDocuments() );
					SmartCAT::debug( "Created '{$task_name}'" );
				}
				$task->set_status( Task::STATUS_CREATED );
				$task->set_project_id( $project_id );
				$task_repository->save( $task );
			} catch ( \Throwable $e ) {
				if ( $e instanceof ClientErrorException ) {
					$message = "API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}";
				} else {
					$message = "Message: {$e->getMessage()}. Trace: {$e->getTraceAsString()}";
				}
				Logger::error( "Failed send to translate $task_name", $message );
				$task->set_status( Task::STATUS_FAILED );
				$task_repository->save( $task );
				$statistic_repository->mark_failed_by_task_id( $task->get_id() );
			}
		}
	}
}
