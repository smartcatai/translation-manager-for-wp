<?php

namespace SmartCAT\WP\Admin;

use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\DITrait;
use SmartCAT\WP\WP\InitInterface;

class Statistics implements InitInterface {
	use DITrait;

	public function plugin_init() {
		add_action( 'delete_post', [ self::class, 'clear_stat' ] );
	}

	public static function clear_stat($pid) {
		$container = self::get_container();
		/** @var StatisticRepository $statisticsRepository */
		$statisticsRepository = $container->get('entity.repository.statistic');
		if ($pid && is_int( $pid )) {
			$statisticsRepository->delete_by_post_id( $pid );
		}
	}
}