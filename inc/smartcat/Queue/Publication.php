<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 19.06.2017
 * Time: 21:45
 */

namespace SmartCAT\WP\Queue;

use Http\Client\Common\Exception\ClientErrorException;
use Psr\Container\ContainerInterface;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;

/** Обработка очереди "Публикация перевода" */
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
		// Actions to perform
		if ( ! SmartCAT::is_active() ) {
			sleep( 10 );

			return $item;
		}

        SmartCAT::debug("[Publication] Start queue '{$item}'");

        /** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var StatisticRepository $statistic_repository */
		$statistic_repository = $container->get( 'entity.repository.statistic' );

		/** @var SmartCAT $sc */
		$sc = $container->get( 'smartcat' );

		$statistics = $statistic_repository->get_one_by( [ 'documentID' => $item ] );
		try {
			if ( $statistics && $statistics->get_status() == 'sended' ) {
                SmartCAT::debug("[Publication] Export '{$item}'");
				$task = $sc->getDocumentExportManager()->documentExportRequestExport( [ 'documentIds' => [ $statistics->get_document_id() ] ] );
				if ( $task->getId() ) {
					$statistics->set_status( 'export' )
					           ->set_error_count( 0 );
					$statistic_repository->update( $statistics );
					/** @var CreatePost $queue */
					$queue = $container->get( 'core.queue.post' );
                    SmartCAT::debug("[Publication] Pushing to CreatePost '{$item}'");
                    $queue->push_to_queue( [
						'documentID' => $statistics->get_document_id(),
						'taskID'     => $task->getId()
					] );
                    SmartCAT::debug("[Publication] Pushed to CreatePost '{$item}'");
                }
			}
		} catch ( ClientErrorException $e ) {
			$status_code = $e->getResponse()->getStatusCode();
			if ( $status_code == 404 ) {
				$statistic_repository->delete( $statistics );
                SmartCAT::debug("[Publication] Deleted '{$item}'");
			} else {
				if ( $statistics->get_error_count() < 360 ) {
					$statistics->inc_error_count();
					$statistic_repository->update( $statistics );
					sleep( 10 );

                    SmartCAT::debug("[Publication] new {$statistics->get_error_count()} try of '{$item}'");
                    return $item;
				}
                Logger::error( "Document $item, start download translate",
                    "API error code: {$status_code}. API error message: {$e->getResponse()->getBody()->getContents()}" );
            }
		} catch (\Throwable $e) {
			Logger::error( "Document {$item}, publication translate","Message: {$e->getMessage()}" );
		}

        SmartCAT::debug("[Publication] End queue '{$item}'");

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