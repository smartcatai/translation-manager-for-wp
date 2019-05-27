<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\Cron;

use Http\Client\Common\Exception\ClientErrorException;
use Psr\Container\ContainerInterface;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Task;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\DB\Repository\TaskRepository;
use SmartCAT\WP\Helpers\Language\LanguageConverter;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\Helpers\Utils;
use SmartCAT\WP\WP\Options;

/** Отправка постов на перевод */
class SendToSmartCAT extends CronAbstract
{
    /**
     * @return mixed
     */
    public function get_interval()
    {
        $schedules['1m'] = [
            'interval' => 60,
            'display' => __('Every minute', 'translation-connectors'),
        ];

        return $schedules;
    }

    public function run()
    {
        if (!SmartCAT::is_active()) {
            return;
        }

        SmartCAT::debug("Sending to SmartCat started");

        /** @var ContainerInterface $container */
        $container = Connector::get_container();

        /** @var Options $options */
        $options = $container->get('core.options');

        /** @var TaskRepository $task_repository */
        $task_repository = $container->get('entity.repository.task');

        /** @var StatisticRepository $statistic_repository */
        $statistic_repository = $container->get('entity.repository.statistic');

        /** @var Utils $utils */
        $utils = $container->get('utils');

        /** @var SmartCAT $smartcat */
        $smartcat = $container->get('smartcat');

        $tasks = $task_repository->get_new_task();
        $workflow_stages = $options->get('smartcat_workflow_stages');
        $vendor_id = $options->get('smartcat_vendor_id');
        $project_id = $options->get('smartcat_api_project_id');

        $count = count($tasks);
        SmartCAT::debug("Finded $count tasks to send");

        /** @var LanguageConverter $converter */
        $converter = $container->get('language.converter');

        foreach ($tasks as $task) {
            $file = $utils->getPostToFile($task->get_post_id());
            $task_name = $smartcat::getTaskNameFromStream($file);

            try {
                if (!empty($project_id)) {
                    SmartCAT::debug("Sending '{$task_name}'");
                    $documentModel = $smartcat->createDocument($file);
                    $document = $smartcat->updateProject($documentModel, $project_id);
                    $statistic_repository->link_to_smartcat_document($task, $document);
                    SmartCAT::debug("Sended '{$task_name}'");
                } else {
                    SmartCAT::debug("Creating '{$task_name}'");
                    $smartcat_project = $smartcat->createProject($file, $task, $converter, $workflow_stages, $vendor_id);
                    $project_id = $smartcat_project->getId();
                    $statistic_repository->link_to_smartcat_document($task, $smartcat_project->getDocuments());
                    SmartCAT::debug("Created '{$task_name}'");
                }
                $task->set_status(Task::STATUS_CREATED);
                $task->set_project_id($project_id);
                $task_repository->update($task);
            } catch (\Throwable $e) {
                if ($e instanceof ClientErrorException) {
                    $message = "API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}";
                } else {
                    $message = "Message: {$e->getMessage()}. Trace: {$e->getTraceAsString()}";
                }
                Logger::error("Send to translate $task_name", $message);
                $task->set_status(Task::STATUS_FAILED);
                $task_repository->update($task);
                $statistic_repository->mark_failed_by_task_id($task->get_id());
            }
        }
    }
}