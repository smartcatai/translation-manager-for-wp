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

namespace SmartCAT\WP\Helpers;

use SmartCat\Client\Model\BilingualFileImportSettingsModel;
use SmartCat\Client\Model\CreateDocumentPropertyWithFilesModel;
use SmartCat\Client\Model\CreateProjectModel;
use SmartCat\Client\Model\DocumentModel;
use SmartCat\Client\Model\ProjectChangesModel;
use SmartCat\Client\Model\ProjectModel;
use SmartCat\Client\Model\ProjectVendorModel;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Entity\Task;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\Helpers\Language\LanguageConverter;

/**
 * Class SmartCAT
 *
 * @package SmartCAT\WP\Helpers
 */
class SmartCAT extends \SmartCat\Client\SmartCat {
	/**
	 * Check can we use Smartcat
	 */
	public static function is_active() {
		$container = Connector::get_container();
		$login     = $container->getParameter( 'smartcat.api.login' );
		$password  = $container->getParameter( 'smartcat.api.password' );
		$server    = $container->getParameter( 'smartcat.api.server' );

		return $login && $password && $server;
	}

	/**
	 * @return bool
	 */
	public static function check_access() {
		$container = Connector::get_container();
		Connector::set_core_parameters();

		try {
			/** @var SmartCAT $smartcat */
			$smartcat = $container->get( 'smartcat' );
			$smartcat->getAccountManager()->accountGetAccountInfo();

			return true;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * @param $s
	 *
	 * @return mixed
	 */
	public static function filter_chars( $s ) {
		return Utils::substr_unicode( str_replace( [ '*', '|', '\\', ':', '"', '<', '>', '?', '/' ], '_', $s ), 0, 90 );
	}

	/**
	 * @param $message
	 */
	public static function debug( $message ) {
		try {
			$container    = Connector::get_container();
			$param_prefix = $container->getParameter( 'plugin.table.prefix' );

			if ( get_option( $param_prefix . 'smartcat_debug_mode', false ) ) {
				$date = ( new \DateTime( 'now' ) )->format( '[Y-m-d H:i:s]' );
				if ( constant( 'SMARTCAT_DEBUG_LOG' ) ) {
					file_put_contents( constant( 'SMARTCAT_DEBUG_LOG' ), "{$date} {$message}" . PHP_EOL, FILE_APPEND );
				}
			}
		} catch ( \Throwable $e ) {
			// Nothing to do.
		}
	}

	/**
	 * @param Task $task
	 * @param $file
	 *
	 * @return ProjectModel
	 * @throws \Exception
	 */
	public function create_project( $task ) {
		/** @var LanguageConverter $language_converter */
		$language_converter = Connector::get_container()->get( 'language.converter' );

		$source_language  = $language_converter->get_sc_code_by_wp( $task->get_source_language() )->get_sc_code();
		$target_languages = array_map(
			function ( $language ) use ( $language_converter ) {
				return $language_converter->get_sc_code_by_wp( $language )->get_sc_code();
			},
			$task->get_target_languages()
		);

		$project_model = new CreateProjectModel();
		$project_model->setName( $this->get_task_name( $task ) );
		$project_model->setSourceLanguage( $source_language );
		$project_model->setTargetLanguages( $target_languages );
		$project_model->setWorkflowStages( $task->get_workflow_stages() );

		if ( $task->get_vendor_id() ) {
			$project_model->setAssignToVendor( true );
			$project_model->setVendorAccountIds( [ $task->get_vendor_id() ] );
		} else {
			$project_model->setAssignToVendor( false );
		}

		$project_model->setDescription( 'WordPress Smartcat Connector' );
		$project_model->setExternalTag( 'source:WPPL' );

		$smartcat_project = $this->getProjectManager()->projectCreateProject( $project_model );

		return $smartcat_project;
	}

	/**
	 * @param Task $task
	 * @param $document_model CreateDocumentPropertyWithFilesModel
	 *
	 * @return \SmartCat\Client\Model\DocumentModel
	 */
	public function update_project( $task, $document_model ) {
		/** @var ProjectModel $sc_project */
		$sc_project = $this->getProjectManager()->projectGet( $task->get_project_id() );

		$sc_documents      = $sc_project->getDocuments();
		$sc_document_names = array_map(
			function ( DocumentModel $value ) {
				return $value->getName() . '.html';
			},
			$sc_documents
		);

		$index = array_search( $document_model->getFile()['fileName'], $sc_document_names, true );

		if ( false !== $index ) {
			Logger::event( 'info', "Document update: '{$sc_documents[ $index ]->getName()}'" );
			$document = $this->getDocumentManager()->documentUpdate(
				[
					'documentId'   => $sc_documents[ $index ]->getId(),
					'uploadedFile' => $document_model->getFile(),
				]
			);
		} else {
			Logger::event( 'info', "Document add: '{$document_model->getFile()['fileName']}'" );
			$document = $this->getProjectManager()->projectAddDocument(
				[
					'documentModel' => [ $document_model ],
					'projectId'     => $task->get_project_id(),
				]
			);
		}

		if ( $sc_project->getExternalTag() !== 'source:WPPL' ) {
			$update_model = ( new ProjectChangesModel() )
				->setName( $sc_project->getName() )
				->setDescription( $sc_project->getDescription() )
				->setVendorAccountIds(
					array_map(
						function ( ProjectVendorModel $vendor_model ) {
							return $vendor_model->getVendorAccountId();
						},
						$sc_project->getVendors()
					)
				)
				->setExternalTag( 'source:WPPL' );
			$this->getProjectManager()->projectUpdateProject( $task->get_project_id(), $update_model );
		}

		return is_array( $document ) ? array_shift( $document ) : $document;
	}

	/**
	 * @param Task $task
	 *
	 * @throws \Exception
	 */
	public function get_task_name( $task ) {
		$titles = [];
		/** @var StatisticRepository $statistic_repository */
		$statistic_repository = Connector::get_container()->get( 'entity.repository.statistic' );
		$statistics           = $statistic_repository->get_all_by( [ 'taskId' => $task->get_id() ] );

		foreach ( $statistics as $statistic ) {
			$post     = get_post( $statistic->get_post_id() );
			$titles[] = $post->post_title;
		}

		$titles = array_unique( $titles );
		$result = self::filter_chars( implode( ' ,', $titles ) );
		$result = Utils::substr_unicode( $result, 0, 94 );

		return trim( $result );
	}

	/**
	 * @param $file
	 * @param Statistics $statistic
	 *
	 * @return CreateDocumentPropertyWithFilesModel
	 * @throws Language\Exceptions\LanguageNotFoundException
	 */
	public function create_document( $file, $statistic ) {
		/** @var LanguageConverter $language_converter */
		$language_converter = Connector::get_container()->get( 'language.converter' );
		$target_language    = $language_converter->get_sc_code_by_wp( $statistic->get_target_language() )->get_sc_code();
		$filename           = self::filter_chars( self::get_task_name_from_stream( $file, false ) ) . "({$target_language}).html";

		$bilingual_file_import_settings = new BilingualFileImportSettingsModel();
		$bilingual_file_import_settings
			->setConfirmMode( 'none' )
			->setLockMode( 'none' )
			->setTargetSubstitutionMode( 'all' );
		$document_model = new CreateDocumentPropertyWithFilesModel();
		$document_model->setBilingualFileImportSettings( $bilingual_file_import_settings );
		$document_model->setExternalId( $statistic->get_id() );
		$document_model->setTargetLanguages( [ $target_language ] );
		$document_model->attachFile( $file, $filename );

		return $document_model;
	}

	/**
	 * @param $file
	 * @param bool $with_extension
	 *
	 * @return string|string[]|null
	 */
	public static function get_task_name_from_stream( $file, $with_extension = false ) {
		$meta_data = stream_get_meta_data( $file );
		$filename  = basename( $meta_data['uri'] );

		if ( ! $with_extension ) {
			$filename = preg_replace( '/^(.*?)\.(.*?)$/', '\1', $filename );
		}

		return $filename;
	}
}
