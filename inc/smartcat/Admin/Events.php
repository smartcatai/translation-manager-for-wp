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

use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Entity\Task;
use SmartCAT\WP\DB\Repository\ProfileRepository;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\DB\Repository\TaskRepository;
use SmartCAT\WP\DITrait;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\WP\HookInterface;

/**
 * Class Events
 *
 * @package SmartCAT\WP
 */
class Events implements HookInterface {
	use DITrait;

	/**
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post_before Post before changes.
	 * @param \WP_Post $post_after Post after changes.
	 */
	public static function post_update_hook( $post_id, $post_before, $post_after ) {
		if ( ! in_array( $post_after->post_type, [ 'page', 'post' ], true ) ) {
			return;
		}

		if ( 'publish' === $post_after->post_status && strpos( $post_before->post_status, 'draft' ) !== false ) {
			$is_created = true;
		} elseif ( 'publish' === $post_after->post_status && 'publish' === $post_before->post_status ) {
			if ( $post_after->post_title === $post_before->post_title && $post_after->post_content === $post_before->post_content ) {
				return;
			}
			$is_created = false;
		} else {
			return;
		}

		$container = self::get_container();

		try {
			/** @var TaskRepository $task_repository */
			$task_repository = $container->get( 'entity.repository.task' );
			/** @var StatisticRepository $statistics_repository */
			$statistics_repository = $container->get( 'entity.repository.statistic' );
			/** @var ProfileRepository $profile_repository */
			$profile_repository = $container->get( 'entity.repository.profile' );
		} catch ( \Exception $e ) {
			Logger::error( 'Can\'t get container', "Reason: {$e->getMessage()} {$e->getTraceAsString()}" );
			return;
		}

		if ( $is_created ) {
			$profiles = $profile_repository->get_all_by( [ 'auto_send' => true ] );
		} else {
			$profiles = $profile_repository->get_all_by( [ 'auto_update' => true ] );
		}

		foreach ( $profiles as $profile ) {
			$source_language  = $profile->get_source_language();
			$target_languages = $profile->get_target_languages();
			$post_language    = pll_get_post_language( $post_id, 'locale' );

			if ( $source_language !== $post_language ) {
				continue;
			}

			$created = 0;

			$task = new Task();
			$task
				->set_source_language( $source_language )
				->set_target_languages( $target_languages )
				->set_profile_id( $profile->get_id() )
				->set_project_id( null );
			$task_id = $task_repository->add( $task );

			$stat = new Statistics();
			$stat->set_task_id( $task_id )
				->set_post_id( $post_id )
				->set_source_language( $source_language )
				->set_progress( 0 )
				->set_words_count( null )
				->set_target_post_id( null )
				->set_document_id( null )
				->set_status( Statistics::STATUS_NEW );

			foreach ( $target_languages as $target_language ) {
				$new_exists = $statistics_repository->get_one_by(
					[
						'status'         => Statistics::STATUS_NEW,
						'postID'         => $post_id,
						'targetLanguage' => $target_language,
						'sourceLanguage' => $source_language,
					]
				);

				if ( $new_exists ) {
					return;
				}

				$new_stat = clone $stat;
				$new_stat->set_target_language( $target_language );
				$statistics_repository->save( $new_stat );
				$created++;
			}

			if ( 0 === $created ) {
				$task_repository->delete_by_id( $task_id );
			}
		}
	}

	/**
	 * Register hooks function
	 */
	public function register_hooks() {
		add_action( 'post_updated', [ self::class, 'post_update_hook' ], 10, 3 );
	}
}
