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
use SmartCat\Client\Model\DocumentModel;
use SmartCat\Client\Model\ProjectChangesModel;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\DB\Repository\TaskRepository;
use SmartCAT\WP\Helpers\Language\LanguageConverter;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;
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
			$post = get_post( $task->get_post_id() );

			$post_body = $post->post_content;

			// Ох уж этот Gutenberg....
			if (!function_exists('has_blocks') || !has_blocks($task->get_post_id())) {
				$post_body = wpautop($post_body);
			}

			$file_body = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" /><title>{$post->post_title}</title></head><body>{$post_body}</body></html>";
			$file_name = "{$post->post_title}.html";
			$file      = fopen( "smartcat://id_{$task->get_post_id()}", "r+" );
			fwrite( $file, $file_body );
			rewind( $file );

			$task_name = $post->post_title;

			if ( ! empty( $project_id ) ) {
				$bilingualFileImportSettings = new BilingualFileImportSettingsModel();
				$bilingualFileImportSettings
					->setConfirmMode( 'none' )
					->setLockMode( 'none' )
					->setTargetSubstitutionMode( 'all' );
				$documentModel = new CreateDocumentPropertyWithFilesModel();
				$documentModel->setBilingualFileImportSettings( $bilingualFileImportSettings );
				$documentModel->attachFile( $file, $sc::filter_chars( $file_name ) );

				try {
				    SmartCAT::debug("Sending '{$task_name}'");
					$sc_project = $sc->getProjectManager()->projectGet( $project_id );

					$sc_documents = $sc_project->getDocuments();
					$sc_document_names = array_map(function (DocumentModel $value) {
						return $value->getName() . ".html";
					}, $sc_documents);

					$index = array_search($documentModel->getFile()['fileName'], $sc_document_names);

					if ($index !== false) {
						$document = $sc->getDocumentManager()->documentUpdate([
							'documentId' => $sc_documents[$index]->getId(),
							'uploadedFile' => $documentModel->getFile()
						]);
					} else {
						$document = $sc->getProjectManager()->projectAddDocument( [
							'documentModel' => [ $documentModel ],
							'projectId'     => $project_id
						] );
					}

					if (is_array($document)) {
						$document = array_shift($document);
					}

					$updateModel = (new ProjectChangesModel())
						->setName($sc_project->getName())
						->setDescription($sc_project->getDescription())
						->setExternalTag('source:WPPL');

					if ($sc_project->getExternalTag() != 'source:WPPL') {
						$sc->getProjectManager()->projectUpdateProject( $project_id, $updateModel );
					}

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
				$project_model = new CreateProjectWithFilesModel();
				$project_model->setName( $sc::filter_chars( $task_name ) );
				try {
					$project_model->setSourceLanguage( $converter->get_sc_code_by_wp( $task->get_source_language() )->get_sc_code() );
					$project_model->setTargetLanguages( array_map( function ( $wp_code ) use ( $converter ) {
						return $converter->get_sc_code_by_wp( $wp_code )->get_sc_code();
					}, $task->get_target_languages() ) );
					$project_model->setWorkflowStages( $workflow_stages );

					if ( $vendor_id ) {
						$project_model->setAssignToVendor( true );
						$project_model->setVendorAccountIds( [$vendor_id] );
					} else {
						$project_model->setAssignToVendor( false );
					}

					$project_model->setExternalTag('source:WPPL');
					$project_model->attacheFile( $file, $sc::filter_chars( $file_name ) );

					$smartcat_project = $sc->getProjectManager()->projectCreateProjectWithFiles( $project_model );

					$task->set_status( 'created' );
					$task->set_project_id( $smartcat_project->getId() );
					$task_repository->update( $task );

					foreach ( $smartcat_project->getDocuments() as $document ) {
						$statistic_repository->link_to_smartcat_document( $task, $document );
					}
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