<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 19.06.2017
 * Time: 18:50
 */

namespace SmartCAT\WP\Cron;


interface CronInterface {

	/** Получение информации об интервале выполнения задачи */
	public function get_interval();

	/** Код задачи */
	public function run();

}