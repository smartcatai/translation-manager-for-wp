<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 19.06.2017
 * Time: 18:54
 */

namespace SmartCAT\WP\Cron;

use Http\Client\Common\Exception\ClientErrorException;
use Psr\Container\ContainerInterface;
use SmartCat\Client\Model\BilingualFileImportSettingsModel;
use SmartCat\Client\Model\CreateDocumentPropertyWithFilesModel;
use SmartCat\Client\Model\CreateProjectWithFilesModel;
use SmartCat\Client\Model\ProjectChangesModel;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\DB\Repository\TaskRepository;
use SmartCAT\WP\Helpers\Language\LanguageConverter;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\Helpers\Utils;
use SmartCAT\WP\WP\Options;

/** Отправка постов на перевод */
class SendToSmartCAT extends CronAbstract {

	public function get_interval() {
		$schedules['1m'] = [
			'interval' => 60,
			'display'  => __( 'Every minute', 'translation-connectors' ),
		];

		return $schedules;
	}

	public function run() {
		if ( ! SmartCAT::is_active() ) {
			return;
		}
		
		SmartCAT::debug("Sending to SmartCat started");
		
		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var Options $options */
		$options = $container->get( 'core.options' );

		/** @var TaskRepository $task_repository */
		$task_repository = $container->get( 'entity.repository.task' );

		/** @var StatisticRepository $statistic_repository */
        $statistic_repository = $container->get( 'entity.repository.statistic' );

        /** @var Utils $utils */
        $utils = $container->get( 'utils' );

		/** @var SmartCAT $sc */
		$sc = $container->get( 'smartcat' );

		$tasks           = $task_repository->get_new_task();
		$workflow_stages = $options->get( 'smartcat_workflow_stages' );
		$vendor_id       = $options->get( 'smartcat_vendor_id' );
		$project_id      = $options->get( 'smartcat_api_project_id' );

        $count = count($tasks);
        SmartCAT::debug("Finded $count tasks to send");

		/** @var LanguageConverter $converter */
		$converter = $container->get( 'language.converter' );

		foreach ( $tasks as $task ) {
            $file = $utils->getPostToFile($task->get_post_id());
            $task_name = $sc::getTaskNameFromStream($file);

			if ( ! empty( $project_id ) ) {
                $documentModel = $sc->createDocument($file);

				try {
				    SmartCAT::debug("Sending '{$task_name}'");

				    $document = $sc->updateProject($documentModel, $project_id);

					$task->set_status( 'created' );
					$task->set_project_id( $project_id );
					$task_repository->update( $task );

					$statistic_repository->link_to_smartcat_document( $task, $document );
					SmartCAT::debug("Sended '{$task_name}'");
				} catch ( \Exception $e ) {
					if ( $e instanceof ClientErrorException ) {
						$message = "API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}";
					} else {
						$message = "Message: {$e->getMessage()}. Trace: {$e->getTraceAsString()}";
					}
					Logger::error( (string) "Send to translate {$task_name}", $message );
				}

			} else {
			    SmartCAT::debug("Creating '{$task_name}'");

				try {
				    $smartcat_project = $sc->createProject($file, $task, $converter, $workflow_stages, $vendor_id);

					$task->set_status( 'created' );
					$task->set_project_id( $smartcat_project->getId() );
					$task_repository->update( $task );

					$statistic_repository->link_to_smartcat_document( $task, $smartcat_project->getDocuments() );

					SmartCAT::debug("Created '{$task_name}'");
				} catch (\Throwable $e) {
					if ( $e instanceof ClientErrorException ) {
						$message = "API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}";
					} else {
						$message = "Message: {$e->getMessage()}. Trace: {$e->getTraceAsString()}";
					}
					Logger::error( "Send to translate $task_name", $message );
					$task->set_status('failed');
					$task_repository->update($task);
					$statistic_repository->mark_failed_by_task_id($task->get_id());
				}
			}
		}
	}
}