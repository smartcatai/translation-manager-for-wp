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
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\WP\Options;

/**
 * Class Statistic
 *
 * @package SmartCAT\WP\Queue
 */
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
				Logger::event( "statistic", "Send to update statistic '{$item}'");
				$queue->update_statistic( $item );

			} catch ( ClientErrorException $e ) {
				Logger::error( "Document '$item', update statistic", "API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}" );
			} catch ( \Throwable $e ) {
				Logger::error( "Document '$item', update statistic","Message: {$e->getMessage()}" );
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