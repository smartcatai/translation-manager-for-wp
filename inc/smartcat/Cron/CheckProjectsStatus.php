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
    }
}
