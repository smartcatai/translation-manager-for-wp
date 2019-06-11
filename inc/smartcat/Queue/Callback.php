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
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;

/** Обработка очереди "Обработка callback’a" */
class Callback extends QueueAbstract {

	public function update_statistic( $item ) {
		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var StatisticRepository $statistic_repository */
		$statistic_repository = $container->get( 'entity.repository.statistic' );

		/** @var SmartCAT $sc */
		$sc = $container->get( 'smartcat' );

		$statistics = $statistic_repository->get_one_by( [ 'documentID' => $item ] );

		try {
			if ( $statistics ) {
				SmartCAT::debug( "[Callback] Queue update_statistic '{$statistics->get_document_id()}'" );
				$document = $sc->getDocumentManager()->documentGet( [ 'documentId' => $statistics->get_document_id() ] );
				$stages   = $document->getWorkflowStages();
				$progress = 0;

				foreach ( $stages as $stage ) {
					$progress += $stage->getProgress();
				}

				$progress = round( $progress / count( $stages ), 2 );
				$statistics->set_progress( $progress )
						   ->set_words_count( $document->getWordsCount() )
						   ->set_error_count( 0 );

				SmartCAT::debug( "[Callback] Set statistics config for '{$statistics->get_id()}'" );
				$statistic_repository->update( $statistics );

				if ( $document->getStatus() == 'completed' ) {
					/** @var Publication $queue */
					$queue = $container->get( 'core.queue.publication' );
					$queue->push_to_queue( $item );
					SmartCAT::debug( "[Callback] Pushed to publication '{$statistics->get_document_id()}'" );
				}
			}
			SmartCAT::debug( "[Callback] End Queue update_statistic '{$statistics->get_document_id()}'" );
		} catch ( ClientErrorException $e ) {
			if ( $e->getResponse()->getStatusCode() == 404 ) {
				$statistic_repository->delete( $statistics );
				SmartCAT::debug( "[Callback] Deleted '{$statistics->get_document_id()}'" );
			} else {
				Logger::error( "Document update statistic",
				"API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}" );
			}
		} catch ( \Throwable $e ) {
			Logger::error( "Document update statistic", "Error: {$e->getMessage()}" );
		}
	}

	protected $action = 'smartcat_callback_async';

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
		try {
			$this->update_statistic( $item );
		} catch ( ClientErrorException $e ) {
			/** @var ContainerInterface $container */
			$container = Connector::get_container();

			/** @var StatisticRepository $statistic_repository */
			$statistic_repository = $container->get( 'entity.repository.statistic' );

			$statistics = $statistic_repository->get_one_by( [ 'documentID' => $item ] );
			if ( $statistics && $statistics->get_error_count() < 360 ) {
				$statistics->inc_error_count();
				$statistic_repository->update( $statistics );
				sleep( 10 );

				SmartCAT::debug( "[Callback] new {$statistics->get_error_count()} try '{$statistics->get_document_id()}'" );
				return $item;
			}
			Logger::error( "Document $item, update statistic",
				"API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}" );

		} catch ( \Throwable $e ) {
			Logger::error( "Document $item, update statistic", "Error: {$e->getMessage()}" );
		}

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
		/** @var Publication $queue */
		$container = Connector::get_container();
		$queue = $container->get( 'core.queue.publication' );
		$queue->save()->dispatch();
		// Show notice to user or perform some other arbitrary task...
	}
}