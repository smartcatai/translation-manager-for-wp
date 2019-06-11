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
use SmartCat\Client\Model\CreateProjectWithFilesModel;
use SmartCat\Client\Model\DocumentModel;
use SmartCat\Client\Model\ProjectChangesModel;
use SmartCat\Client\Model\ProjectModel;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Task;
use SmartCAT\WP\Helpers\Language\LanguageConverter;

class SmartCAT extends \SmartCat\Client\SmartCat
{
	/**
	 * Проверяет можно ли использовать АПИ. Имеются ли сохраненые в настройках данные для доступа к АПИ
	 */
	public static function is_active()
	{
		$container = Connector::get_container();
		$login = $container->getParameter( 'smartcat.api.login' );
		$password = $container->getParameter( 'smartcat.api.password' );
		$server = $container->getParameter( 'smartcat.api.server' );

		return $login && $password && $server;
	}

	/**
	 * @param $s
	 * @return mixed
	 */
	public static function filter_chars( $s )
	{
		return str_replace( ['*', '|', '\\', ':', '"', '<', '>', '?', '/'], '_', $s );
	}

	/**
	 * @param $message
	 */
	public static function debug( $message )
	{
		try {
			if ( constant( 'SMARTCAT_DEBUG_ENABLED' ) === true ) {
				$date = ( new \DateTime( 'now' ) )->format( '[Y-m-d H:i:s]' );
				if ( constant( 'SMARTCAT_DEBUG_LOG' ) ) {
					file_put_contents( constant( 'SMARTCAT_DEBUG_LOG' ), "{$date} {$message}" . PHP_EOL, FILE_APPEND );
				}
			}
		} catch ( \Throwable $e ) {
		}
	}

	/**
	 * @param $file
	 * @param $task Task
	 * @param $converter LanguageConverter
	 * @param $workflow_stages
	 * @param $vendor_id
	 * @return ProjectModel
	 * @throws Language\Exceptions\LanguageNotFoundException
	 */
	public function createProject( $file, $task, $converter, $workflow_stages, $vendor_id )
	{
		$project_model = new CreateProjectWithFilesModel();
		$project_model->setName( self::filter_chars( self::getTaskNameFromStream( $file ) ) );
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

		$project_model->setDescription( 'Wordpress Smartcat сonnector' );
		$project_model->setExternalTag( 'source:WPPL' );
		$project_model->attacheFile( $file, self::filter_chars( self::getTaskNameFromStream( $file, true ) ) );

		$smartcat_project = $this->getProjectManager()->projectCreateProjectWithFiles( $project_model );

		return $smartcat_project;
	}

	/**
	 * @param $documentModel CreateDocumentPropertyWithFilesModel
	 * @param $project_id
	 * @return \Psr\Http\Message\ResponseInterface|\SmartCat\Client\Model\DocumentModel
	 */
	public function updateProject( $documentModel, $project_id )
	{
		/** @var ProjectModel $sc_project */
		$sc_project = $this->getProjectManager()->projectGet( $project_id );

		$sc_documents = $sc_project->getDocuments();
		$sc_document_names = array_map( function ( DocumentModel $value ) {
			return $value->getName() . ".html";
		}, $sc_documents );

		$index = array_search( $documentModel->getFile()['fileName'], $sc_document_names );

		if ( $index !== false ) {
			$document = $this->getDocumentManager()->documentUpdate( [
				'documentId' => $sc_documents[$index]->getId(),
				'uploadedFile' => $documentModel->getFile()
			] );
		} else {
			$document = $this->getProjectManager()->projectAddDocument( [
				'documentModel' => [ $documentModel ],
				'projectId'	 => $project_id
			] );
		}

		if ( is_array( $document ) ) {
			$document = array_shift( $document );
		}

		$updateModel = ( new ProjectChangesModel() )
			->setName( $sc_project->getName() )
			->setDescription( $sc_project->getDescription() )
			->setExternalTag( 'source:WPPL' );

		if ( $sc_project->getExternalTag() != 'source:WPPL' ) {
			$this->getProjectManager()->projectUpdateProject( $project_id, $updateModel );
		}

		return $document;
	}

	/**
	 * @param $file
	 * @return CreateDocumentPropertyWithFilesModel
	 */
	public function createDocument( $file )
	{
		$filename = self::getTaskNameFromStream( $file, true );

		$bilingualFileImportSettings = new BilingualFileImportSettingsModel();
		$bilingualFileImportSettings
			->setConfirmMode( 'none' )
			->setLockMode( 'none' )
			->setTargetSubstitutionMode( 'all' );
		$documentModel = new CreateDocumentPropertyWithFilesModel();
		$documentModel->setBilingualFileImportSettings( $bilingualFileImportSettings );
		$documentModel->attachFile( $file, self::filter_chars( $filename ) );

		return $documentModel;
	}

	/**
	 * @param $file
	 * @param bool $withExtension
	 * @return string|string[]|null
	 */
	public static function getTaskNameFromStream( $file, $withExtension = false )
	{
		$meta_data = stream_get_meta_data( $file );
		$filename = basename( $meta_data["uri"] );

		if ( !$withExtension ) {
			$filename = preg_replace( '/^( .* )\.( .*? )$/', '\1', $filename );
		}

		return $filename;
	}
}