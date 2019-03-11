<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 19.06.2017
 * Time: 18:54
 */

namespace SmartCAT\WP\Cron;

/** Отчистка удаленных проектов */
class ClearDeletedProject extends CronAbstract {

	public function get_interval() {
		$schedules['daily'] = [
			'interval' => DAY_IN_SECONDS,
			'display'  => __( 'Once Daily', 'translation-connectors' )
		];

		return $schedules;
	}

	public function run() {

	}
}