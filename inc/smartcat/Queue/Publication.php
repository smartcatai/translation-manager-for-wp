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

namespace SmartCAT\WP\Queue;

use Http\Client\Common\Exception\ClientErrorException;
use Psr\Container\ContainerInterface;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;

/**
 * Class Publication
 *
 * @package SmartCAT\WP\Queue
 */
class Publication extends QueueAbstract {
	protected $action = 'smartcat_publication_async';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		if ( ! SmartCAT::is_active() ) {
			sleep( 10 );

			return $item;
		}

		Logger::event( 'publication', "End queue Start queue '{$item}'" );

		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var StatisticRepository $statistic_repository */
		$statistic_repository = $container->get( 'entity.repository.statistic' );

		/** @var SmartCAT $sc */
		$sc = $container->get( 'smartcat' );

		$statistics = $statistic_repository->get_one_by( [ 'documentID' => $item ] );
		try {
			if ( $statistics && $statistics->get_status() === Statistics::STATUS_SENDED ) {
				Logger::event( 'publication', "Export '{$item}'" );

				$task = $sc->getDocumentExportManager()
					->documentExportRequestExport( [ 'documentIds' => [ $statistics->get_document_id() ] ] );
				if ( $task->getId() ) {
					$statistics
						->set_status( Statistics::STATUS_EXPORT )
						->set_error_count( 0 );
					$statistic_repository->update( $statistics );
					/** @var CreatePost $queue */
					$queue = $container->get( 'core.queue.post' );

					Logger::event( 'publication', "Pushing to CreatePost '{$item}'" );
					$queue->push_to_queue(
						[
							'documentID' => $statistics->get_document_id(),
							'taskID'     => $task->getId(),
						]
					);
					Logger::event( 'publication', "Pushed to CreatePost '{$item}'" );
				}
			}
		} catch ( ClientErrorException $e ) {
			$status_code = $e->getResponse()->getStatusCode();
			if ( 404 === $status_code ) {
				$statistic_repository->delete( $statistics );
				Logger::event( 'publication', "Deleted '{$item}'" );
			} else {
				if ( $statistics->get_error_count() < 360 ) {
					$statistics->inc_error_count();
					$statistic_repository->update( $statistics );
					sleep( 10 );

					Logger::event( 'publication', "New {$statistics->get_error_count()} try of '{$item}'" );
					return $item;
				}
				Logger::error(
					"Document $item, start download translate",
					"API error code: {$status_code}. API error message: {$e->getResponse()->getBody()->getContents()}"
				);
			}
		} catch ( \Throwable $e ) {
			Logger::error( "Document {$item}, publication translate", "Message: {$e->getMessage()}" );
		}

		Logger::event( 'publication', "End queue '{$item}'" );

		return false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();
		// Show notice to user or perform some other arbitrary task...
		/** @var CreatePost $queue */
		/** @var ContainerInterface $container */
		$container = Connector::get_container();
		$queue     = $container->get( 'core.queue.post' );
		$queue->save()->dispatch();
	}
}
