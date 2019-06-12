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

namespace SmartCAT\WP\Admin;

use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\DITrait;
use SmartCAT\WP\WP\InitInterface;

/**
 * Class Statistics
 *
 * @package SmartCAT\WP\Admin
 */
class Statistics implements InitInterface {
	use DITrait;

	/**
	 *
	 */
	public function plugin_init() {
		add_action( 'delete_post', [ self::class, 'clear_stat' ] );
	}

	/**
	 * @param $pid
	 *
	 * @throws \Exception
	 */
	public static function clear_stat( $pid ) {
		$container = self::get_container();

		/** @var StatisticRepository $statistic_repository */
		$statistic_repository = $container->get( 'entity.repository.statistic' );
		if ( $pid && is_int( $pid ) ) {
			$statistic_repository->delete_by_post_id( $pid );
		}
	}
}
