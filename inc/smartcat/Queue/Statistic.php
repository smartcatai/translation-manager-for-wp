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
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\WP\Options;

/** Обработка очереди "Обновление статистики" */
class Statistic extends QueueAbstract {
	protected $action = 'smartcat_statistic_async';

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
		if ( SmartCAT::is_active() ) {
			try {
				/** @var ContainerInterface $container */
				$container = Connector::get_container();

				/** @var \SmartCAT\WP\Queue\Callback $queue */
				$queue = $container->get( 'core.queue.callback' );
				$queue->update_statistic( $item );

			} catch ( ClientErrorException $e ) {
				Logger::error( "Document $item, update statistic", "API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}" );
			} catch (\Throwable $e) {
			    Logger::error( "Document $item, update statistic","Message: {$e->getMessage()}" );
			}
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
		/** @var ContainerInterface $container */
		$container = Connector::get_container();
		/** @var Publication $queue */
		$queue = $container->get( 'core.queue.publication' );
		$queue->save()->dispatch();
		/** @var Options $options */
		$options = $container->get( 'core.options' );
		$options->set( 'statistic_queue_active', false );

		// Show notice to user or perform some other arbitrary task...
	}
}