<?php

namespace SmartCAT\WP\Admin;

use Psr\Container\ContainerInterface;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\Queue\Statistic;
use SmartCAT\WP\WP\HookInterface;
use SmartCAT\WP\WP\Options;

//Надо сделать общий абстрактный класс для всех AJAX запросов, реализующий всю основную логику
final class StatisticsAjax implements HookInterface {

	private $prefix;

	public function __construct( $prefix ) {
		$this->prefix = $prefix;
	}

	/**
	 * Ручной запуск обновления статистики
	 */
	static public function start_refresh_statistic() {
		$ajax_response = new AjaxResponse();
		if ( ! current_user_can( 'publish_posts' ) ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
			wp_die();
		}

		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var Options $options */
		$options = $container->get( 'core.options' );
		$queue   = null;
        
		if ( ! $options->get( 'statistic_queue_active' ) ) {
        
			/** @var StatisticRepository $statistic_repository */
			$statistic_repository = $container->get( 'entity.repository.statistic' );
			$statistics           = $statistic_repository->get_sended();
			if ( count( $statistics ) > 0 ) {
				$options->set( 'statistic_queue_active', true );
				/** @var Statistic $queue */
				$queue = $container->get( 'core.queue.statistic' );
				foreach ( $statistics as $statistic ) {
					if ( $statistic->get_error_count() > 0 ) {
						$statistic->set_error_count( 0 );
						$statistic_repository->persist( $statistic );
					}

					$queue->push_to_queue( $statistic->get_document_id() );
				}
				$statistic_repository->flush();
				$queue->save()->dispatch();
			}
		}

		$ajax_response->send_success( 'ok' );
		wp_die();
	}

	static public function check_refresh_statistic_status() {
		$ajax_response = new AjaxResponse();
		if ( ! current_user_can( 'publish_posts' ) ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
			wp_die();
		}

		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var Options $options */
		$options = $container->get( 'core.options' );
		$ajax_response->send_success( 'ok',
			[ 'statistic_queue_active' => boolval( $options->get( 'statistic_queue_active' ) ) ] );
		wp_die();
	}

	public function register_hooks() {
		if ( wp_doing_ajax() ) {
			add_action( "wp_ajax_{$this->prefix}start_statistic", [ self::class, 'start_refresh_statistic' ] );
			add_action( "wp_ajax_{$this->prefix}check_statistic", [ self::class, 'check_refresh_statistic_status' ] );
		}
	}

}