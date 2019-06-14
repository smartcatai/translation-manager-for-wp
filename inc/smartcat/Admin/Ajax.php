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

use Http\Client\Common\Exception\ClientErrorException;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Entity\Task;
use SmartCAT\WP\DB\Repository\ProfileRepository;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\DB\Repository\TaskRepository;
use SmartCAT\WP\DITrait;
use SmartCAT\WP\Helpers\Language\LanguageConverter;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\Helpers\Utils;
use SmartCAT\WP\WP\HookInterface;
use SmartCAT\WP\WP\Options;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class Ajax
 *
 * @package SmartCAT\WP\Admin
 */
final class Ajax implements HookInterface {
	use DITrait;

	static public function validate_settings() {
		// валидация на js страницы с настройками
		// в общем виде - проверка на стороне смартката правильности логина и пароля

		$ajax_response = new AjaxResponse();
		if ( ! current_user_can( 'publish_posts' ) ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
			wp_die();
		}

		$container = self::get_container();
		$prefix    = $container->getParameter( 'plugin.table.prefix' );

		$data = [ 'isActive' => SmartCAT::is_active() ];

		$required_parameters = [ $prefix . 'smartcat_api_login', $prefix . 'smartcat_api_password' ];
		$parameters          = $_POST;

		$login = $password = '';
		/** @var Utils $utils */
		$utils = $container->get( 'utils' );
		if ( ! $utils->is_array_in_array( $required_parameters, array_keys( $parameters ) ) ) {
			$ajax_response->send_error( __( 'Login and password are required', 'translation-connectors' ), $data );
		} elseif ( empty( $login = $parameters[ $prefix . 'smartcat_api_login' ] ) || empty( $password = $parameters[ $prefix . 'smartcat_api_password' ] ) ) {
			$ajax_response->send_error( __( 'Login and password are required', 'translation-connectors' ), $data );
		}

		$server = $parameters[ $prefix . 'smartcat_api_server' ];

		/** @var Options $options */
		$options           = $container->get( 'core.options' );
		$previous_login    = $options->get_and_decrypt( 'smartcat_api_login' );
		$previous_password = $options->get_and_decrypt( 'smartcat_api_password' );
		$previous_server   = $options->get( 'smartcat_api_server' );

		if ( $password === '******' ) {
			$password = $previous_password; //упрощаю логику, иначе выходил уже набор костылей
		}

		//проверка, что с кредами все ок
		$account_info = null;
		try {
			$api          = new \SmartCat\Client\SmartCAT( $login, $password, $server );
			$account_info = $api->getAccountManager()->accountGetAccountInfo();
			$is_ok        = ( bool ) $account_info->getId();
			if ( ! $is_ok ) {
				throw new Exception( 'Invalid username or password' );
			}
		} catch ( \Exception $e ) {
			if ( $e->getMessage() === 'Invalid username or password' ) {
				$ajax_response->send_error( __( 'Invalid username or password', 'translation-connectors' ), $data );
			} else {
				$ajax_response->send_error( 'nok', $data );
			}
		}

		//согласно требованиям - если коллбэк уже висел, нужно сперва его дропнуть
		if ( ! empty( $previous_login ) && ! empty( $previous_password ) && ! empty( $previous_server ) ) {
			try {
				$sc = new SmartCAT( $previous_login, $previous_password, $previous_server );
				$sc->getCallbackManager()->callbackDelete();
			} catch ( \Exception $e ) {
				$data['message'] = $e->getMessage();

				if ( $e instanceof ClientErrorException ) {
					$message = "API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}";
				} else {
					$message = "Message: {$e->getMessage()}. Trace: {$e->getTraceAsString()}";
				}

				Logger::error( "Callback delete failed, user {$previous_login}", $message );

				$ajax_response->send_error(
					__( 'Problem with deleting of previous callback', 'translation-connectors' ),
					$data
				);
			}
		}

		try {
			Connector::set_core_parameters();
			$callback_handler = $container->get( 'callback.handler.smartcat' );
			$callback_handler->register_callback();
		} catch ( \Exception $e ) {
			$data['message'] = $e->getMessage();

			if ( $e instanceof ClientErrorException ) {
				$message = "API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}";
			} else {
				$message = "Message: {$e->getMessage()}. Trace: {$e->getTraceAsString()}";
			}

			Logger::error( "Callback register failed, user {$login}", $message );

			$ajax_response->send_error( __( 'Problem with setting of new callback', 'translation-connectors' ), $data );
		}

		//сохраняем account_name
		if ( $account_info && $account_info->getName() ) {
			/** @var Options $options */
			$options = $container->get( 'core.options' );
			$options->set( 'smartcat_account_name', $account_info->getName() );
		}

		$ajax_response->send_success( 'ok', $data );
		wp_die();
	}

	/**
	 * @param Statistics $statistic
	 *
	 * @throws \Exception
	 */
	public function refresh_translation() {
		$ajax_response = new AjaxResponse();
		if ( ! current_user_can( 'publish_posts' ) ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
			wp_die();
		}

		$data      = [ 'isActive' => SmartCAT::is_active() ];
		$container = self::get_container();

		/** @var StatisticRepository $statistic_repository */
		$statistic_repository = $container->get( 'entity.repository.statistic' );

		if ( ! empty( $_POST['stat_id'] ) && intval( $_POST['stat_id'] ) ) {
			$statistic = $statistic_repository->get_one_by( [ 'id' => intval( $_POST['stat_id'] ) ] );
			if ( $statistic->get_target_post_id() ) {
				$statistic->set_status( 'sended' );
				$statistic_repository->update( $statistic );

				$data["statistic"] = [
					'status' => __( 'In progress', 'translation-connectors' )
				];

				$ajax_response->send_success( 'ok', $data );
			}

			wp_die();
		}

		$ajax_response->send_error( 'nok', $data );

		wp_die();
	}

	/**
	 * Create tasks for smartcat
	 *
	 * @throws \Exception
	 */
	static public function send_to_smartcat() {
		$ajax_response = new AjaxResponse();
		$verify_nonce  = wp_verify_nonce(
			wp_unslash( sanitize_key( $_POST['sc_send_nonce'] ?? null ) ),
			'sc_send_nonce'
		);

		if ( ! current_user_can( 'publish_posts' ) || ! $verify_nonce ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
			die();
		}

		$post = sanitize_post( $_POST );

		$container = self::get_container();
		/** @var TaskRepository $task_repository */
		$task_repository = $container->get( 'entity.repository.task' );
		/** @var StatisticRepository $statistics_repository */
		$statistics_repository = $container->get( 'entity.repository.statistic' );
		/** @var ProfileRepository $profiles_repository */
		$profiles_repository = $container->get( 'entity.repository.profile' );

		$posts   = explode( ',', $post['posts'] );
		$profile = $profiles_repository->get_one_by_id( $post['sc-profile'] );

		if ( empty( $posts ) || empty( $profile ) ) {
			$ajax_response->send_error( __( 'Incorrrect request', 'translation-connectors' ), [], 403 );
			die();
		}

		$task = new Task();
		$task->set_source_language( $profile->get_source_language() )
			->set_target_languages( $profile->get_target_languages() )
			->set_status( Task::STATUS_NEW )
			->set_project_id( $profile->get_project_id() ?? null );

		$task_id = $task_repository->add( $task );

		foreach ( $posts as $post_id ) {
			if ( $task_id ) {
				$stat = new Statistics();
				$stat->set_task_id( $task_id )
					->set_post_id( $post_id )
					->set_source_language( $profile->get_source_language() )
					->set_progress( 0 )
					->set_words_count( null )
					->set_target_post_id( null )
					->set_document_id( null )
					->set_status( Statistics::STATUS_NEW );

				$data['stats'] = [];

				foreach ( $profile->get_target_languages() as $target_language ) {
					$new_stat = clone $stat;
					$new_stat->set_target_language( $target_language );
					$stat_id = $statistics_repository->add( $new_stat );

					if ( $stat_id ) {
						array_push( $data['stats'], $stat_id );
					} else {
						array_push( $data['failed-stats'], $stat_id );
					}
				}

				if ( count( $data['stats'] ) !== count( $profile->get_target_languages() ) ) {
					$ajax_response->send_error( __( 'Not all stats was created', 'translation-connectors' ) );
				}
			} else {
				$data['task'] = $task;
				$ajax_response->send_error( __( 'Task was not created', 'translation-connectors' ), $data );
			}
		}

		if ( $task_id ) {
			$ajax_response->send_success( 'ok', $data );
		} else {
			$ajax_response->send_error( 'Task was not created', $data );
		}

		spawn_cron();

		die();
	}

	/**
	 * Register hooks function
	 */
	public function register_hooks() {
		if ( wp_doing_ajax() ) {
			$container = self::get_container();
			$prefix    = $container->getParameter( 'plugin.table.prefix' );

			add_action( "wp_ajax_{$prefix}validate_settings", [ self::class, 'validate_settings' ] );
			add_action( "wp_ajax_{$prefix}send_to_smartcat", [ self::class, 'send_to_smartcat' ] );
			add_action( "wp_ajax_{$prefix}refresh_translation", [ self::class, 'refresh_translation' ] );
		}
	}
}
