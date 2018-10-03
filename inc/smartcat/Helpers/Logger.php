<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 09.08.2017
 * Time: 15:57
 */

namespace SmartCAT\WP\Helpers;


use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Error;
use SmartCAT\WP\DB\Repository\ErrorRepository;

class Logger {
	private static function add_record(string $type, string $shortMessage, string $message = '') {
		$container = Connector::get_container();

		/** @var ErrorRepository $repository */
		$repository = $container->get('entity.repository.error');

		$error = new Error();
		$error->set_date(new \DateTime())
			->set_type($type)
			->set_short_message($shortMessage)
			->set_message($message);
		$repository->add($error);
	}

	public static function info(string $shortMessage, string $message = '') {
		self::add_record('info', $shortMessage, $message);
	}

	public static function warning(string $shortMessage, string $message = '') {
		self::add_record('warning', $shortMessage, $message);
	}

	public static function error(string $shortMessage, string $message = '') {
		self::add_record('error', $shortMessage, $message);
	}
}