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

use Psr\Container\ContainerInterface;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\Helpers\SmartCAT;

class CheckProjectsStatus extends CronAbstract
{
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

        SmartCAT::debug("Checking documents started");

        /** @var ContainerInterface $container */
        $container = Connector::get_container();

        /** @var StatisticRepository $statistic_repository */
        $statistic_repository = $container->get('entity.repository.statistic');

        /** @var SmartCAT $smartcat */
        $smartcat = $container->get('smartcat');

        $statistics = $statistic_repository->get_sended();
        $count = count($statistics);

        SmartCAT::debug("Finded $count documents to check");

        foreach ($statistics as $statistic) {
            $document = $smartcat->getDocumentManager()->documentGet(
                ['documentId' => $statistic->get_document_id()]
            );

            $stages   = $document->getWorkflowStages();
            $progress = 0;

            foreach ($stages as $stage) {
                $progress += $stage->getProgress();
            }

            $progress = round($progress / count($stages), 2);
            $statistic->set_progress($progress)
                ->set_words_count($document->getWordsCount());
            
            $statistic_repository->update($statistic);
        }

        SmartCAT::debug("Checking documents ended");
    }
}
