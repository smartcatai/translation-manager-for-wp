<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 20.06.2017
 * Time: 20:13
 */

namespace SmartCAT\WP\Helpers;

use SmartCat\Client\Model\BilingualFileImportSettingsModel;
use SmartCat\Client\Model\CreateDocumentPropertyWithFilesModel;
use SmartCat\Client\Model\CreateProjectWithFilesModel;
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
        $login = $container->getParameter('smartcat.api.login');
        $password = $container->getParameter('smartcat.api.password');
        $server = $container->getParameter('smartcat.api.server');

        return $login && $password && $server;
    }

    /**
     * @param $s
     * @return mixed
     */
    public static function filter_chars($s)
    {
        return str_replace(['*', '|', '\\', ':', '"', '<', '>', '?', '/'], '_', $s);
    }

    /**
     * @param $message
     * @throws \Exception
     */
    public static function debug($message)
    {
        if (constant('SMARTCAT_DEBUG_ENABLED') === true) {
            $date = (new \DateTime('now'))->format('[Y-m-d H:i:s]');
            if (constant('SMARTCAT_DEBUG_LOG')) {
                file_put_contents(constant('SMARTCAT_DEBUG_LOG'), "{$date} {$message}" . PHP_EOL, FILE_APPEND);
            }
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
    public function createProject($file, $task, $converter, $workflow_stages, $vendor_id)
    {
        $project_model = new CreateProjectWithFilesModel();
        $project_model->setName( self::filter_chars( self::getTaskNameFromStream($file) ) );
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
        $project_model->attacheFile( $file, self::filter_chars( self::getTaskNameFromStream($file, true) ) );

        $smartcat_project = $this->getProjectManager()->projectCreateProjectWithFiles( $project_model );

        return $smartcat_project;
    }

    /**
     * @param $documentModel
     * @param $project_id
     * @return \Psr\Http\Message\ResponseInterface|\SmartCat\Client\Model\DocumentModel
     */
    public function updateProject($documentModel, $project_id)
    {
        $document = $this->getProjectManager()->projectAddDocument( [
            'documentModel' => [ $documentModel ],
            'projectId'     => $project_id
        ] );

        /** @var ProjectModel $sc_project */
        $sc_project = $this->getProjectManager()->projectGet( $project_id );
        $updateModel = (new ProjectChangesModel())
            ->setName($sc_project->getName())
            ->setDescription($sc_project->getDescription())
            ->setExternalTag('source:WPPL');

        if ($sc_project->getExternalTag() != 'source:WPPL') {
            $this->getProjectManager()->projectUpdateProject( $project_id, $updateModel );
        }

        return array_shift( $document );
    }

    /**
     * @param $file
     * @return CreateDocumentPropertyWithFilesModel
     */
    public function createDocument($file)
    {
        $filename = self::getTaskNameFromStream($file, true);

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
    public static function getTaskNameFromStream($file, $withExtension = false)
    {
        $meta_data = stream_get_meta_data($file);
        $filename = basename($meta_data["uri"]);

        if (!$withExtension) {
            $filename = preg_replace('/^(.*)\.(.*?)$/', '\1', $filename);
        }

        return $filename;
    }
}