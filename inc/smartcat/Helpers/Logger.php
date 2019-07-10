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

namespace SmartCAT\WP\Helpers;

use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Error;
use SmartCAT\WP\DB\Entity\Event;
use SmartCAT\WP\DB\Repository\ErrorRepository;
use SmartCAT\WP\DB\Repository\EventRepository;
use \DateTime;
use \Exception;
use SmartCAT\WP\WP\Options;

/**
 * Class Logger
 *
 * @package SmartCAT\WP\Helpers
 */
class Logger {
	const TYPE_INFO    = 'info';
	const TYPE_WARNING = 'warning';
	const TYPE_ERROR   = 'error';

	/**
	 * Add message handler
	 *
	 * @param string $type Set message type.
	 * @param string $short_message Set short message.
	 * @param string $message Set message.
	 */
	private static function add_record( $type, $short_message, $message = '' ) {
		$error      = new Error();
		$repository = self::get_error_repository();

		SmartCAT::debug( "[{$type}] {$message}" );

		if ( ! $repository ) {
			return;
		}

		try {
			$error->set_date( new DateTime() );
		} catch ( Exception $e ) {
			return;
		}

		$error->set_type( $type )
			->set_short_message( $short_message )
			->set_message( str_replace( '\r\n', PHP_EOL, $message ) );

		$repository->add( $error );
	}

	/**
	 * Add event handler
	 *
	 * @param string $type Set message type.
	 * @param string $message Set message.
	 */
	public static function event( $type, $message ) {
		SmartCAT::debug( "[{$type}] {$message}" );

		if ( ! self::get_options()->get( 'smartcat_events_enabled' ) ) {
			return;
		}

		$event      = new Event();
		$repository = self::get_event_repository();

		if ( ! $repository ) {
			return;
		}

		try {
			$event->set_date( new DateTime() );
		} catch ( Exception $e ) {
			return;
		}

		$event
			->set_type( $type )
			->set_message( str_replace( '\r\n', PHP_EOL, $message ) );

		$repository->save( $event );
	}

	/**
	 * Add an info message
	 *
	 * @param string $short_message Set short message.
	 * @param string $message Set message.
	 */
	public static function info( $short_message, $message = '' ) {
		self::add_record( self::TYPE_INFO, $short_message, $message );
	}

	/**
	 * Add a warning message
	 *
	 * @param string $short_message Set short message.
	 * @param string $message Set message.
	 */
	public static function warning( $short_message, $message = '' ) {
		self::add_record( self::TYPE_WARNING, $short_message, $message );
	}

	/**
	 * Add an error message
	 *
	 * @param string $short_message Set short message.
	 * @param string $message Set message.
	 */
	public static function error( $short_message, $message = '' ) {
		self::add_record( self::TYPE_ERROR, $short_message, $message );
	}

	/**
	 * Get error repository
	 *
	 * @return ErrorRepository|null
	 */
	private static function get_error_repository() {
		try {
			return Connector::get_container()->get( 'entity.repository.error' );
		} catch ( \Throwable $e ) {
			SmartCAT::debug( '[error] Error repository container does not exists' );
			return null;
		}
	}

	/**
	 * Get error repository
	 *
	 * @return EventRepository|null
	 */
	private static function get_event_repository() {
		try {
			return Connector::get_container()->get( 'entity.repository.event' );
		} catch ( \Throwable $e ) {
			SmartCAT::debug( '[error] Event repository container does not exists' );
			return null;
		}
	}

	/**
	 * Get error repository
	 *
	 * @return Options|null
	 */
	private static function get_options() {
		try {
			return Connector::get_container()->get( 'core.options' );
		} catch ( \Throwable $e ) {
			SmartCAT::debug( '[error] Options container does not exists' );
			return null;
		}
	}
}
