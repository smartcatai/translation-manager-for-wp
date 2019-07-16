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
	public function get_interval() {
		$schedules['1m'] = [
			'interval' => 60,
			'display'  => __( 'Every minute', 'translation-connectors' ),
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

		Logger::event( 'cron', 'Sending to Smartсat started' );

		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var TaskRepository $task_repository */
		$task_repository = $container->get( 'entity.repository.task' );

		/** @var StatisticRepository $statistic_repository */
		$statistic_repository = $container->get( 'entity.repository.statistic' );

		/** @var Utils $utils */
		$utils = $container->get( 'utils' );

		/** @var SmartCAT $smartcat */
		$smartcat = $container->get( 'smartcat' );

		/** @var Statistics[] $statistics */
		$statistics = $statistic_repository->get_by_status( Statistics::STATUS_NEW );

		$count = count( $statistics );
		Logger::event( 'cron', "Find $count tasks to send" );

		foreach ( $statistics as $statistic ) {
			$task      = $task_repository->get_one_by_id( $statistic->get_task_id() );
			$file      = $utils->get_post_to_file( $statistic->get_post_id() );
			$file_name = $smartcat::get_task_name_from_stream( $file );

			try {
				if ( ! empty( $task->get_project_id() ) ) {
					Logger::event( 'cron', "Sending '{$file_name}'" );

					$document_model = $smartcat->create_document( $file, $statistic );
					$document       = $smartcat->update_project( $document_model, $task );

					$statistic_repository->link_to_smartcat_document( $task, $document );
					Logger::event( 'cron', "File '{$file_name}' was sent" );
				} else {
					Logger::event( 'cron', "Creating '{$file_name}'" );

					$smartcat_project = $smartcat->create_project( $task, $file );
					$statistic_repository->link_to_smartcat_document( $task, $smartcat_project->getDocuments() );
					$task->set_project_id( $smartcat_project->getId() );
					$task_repository->save( $task );

					Logger::event( 'cron', "File '{$file_name}' was created" );
				}
			} catch ( \Throwable $e ) {
				if ( $e instanceof ClientErrorException ) {
					$message = "API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}";
				} else {
					$message = "Message: {$e->getMessage()}. Trace: {$e->getTraceAsString()}";
				}
				Logger::error( "Failed send to translate '{$file_name}'", $message );
				$statistic->set_status( Statistics::STATUS_FAILED );
				$statistic_repository->save( $statistic );
			}
		}

		Logger::event( 'cron', 'Sending to Smartсat ended' );
	}
}
