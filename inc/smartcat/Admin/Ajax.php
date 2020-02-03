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

use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Profile;
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Entity\Task;
use SmartCAT\WP\DB\Repository\ProfileRepository;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\DB\Repository\TaskRepository;
use SmartCAT\WP\DITrait;
use SmartCAT\WP\Helpers\CronHelper;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\Helpers\Utils;
use SmartCAT\WP\WP\HookInterface;
use SmartCAT\WP\WP\Options;

/**
 * Class Ajax
 *
 * @package SmartCAT\WP\Admin
 */
final class Ajax implements HookInterface {
	use DITrait;

	/**
	 * Validating settings before save on Smartcat side
	 */
	public static function validate_settings() {
		$ajax_response = new AjaxResponse();
		$verify_nonce  = wp_verify_nonce(
			wp_unslash( sanitize_key( $_POST['sc_validate_settings_nonce'] ?? null ) ),
			'sc_validate_settings'
		);

		if ( ! current_user_can( 'publish_posts' ) || ! $verify_nonce ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
		}

		$container = self::get_container();
		$prefix    = $container->getParameter( 'plugin.table.prefix' );

		$data = [ 'isActive' => SmartCAT::is_active() ];

		$required_parameters = [ $prefix . 'smartcat_api_login', $prefix . 'smartcat_api_password' ];
		$parameters          = sanitize_post( $_POST );

		$login    = '';
		$password = '';
		$utils    = null;
		$options  = null;

		try {
			/** @var Utils $utils */
			$utils = $container->get( 'utils' );
			/** @var Options $options */
			$options = $container->get( 'core.options' );
		} catch ( \Exception $e ) {
			Logger::error( 'Can\'t get container', "Reason: {$e->getMessage()} {$e->getTraceAsString()}" );
			$ajax_response->send_error( $e->getMessage(), [], 400 );
		}

		if ( ! $utils->is_array_in_array( $required_parameters, array_keys( $parameters ) ) ) {
			$ajax_response->send_error( __( 'Login and password are required', 'translation-connectors' ), $data );
		} elseif ( empty( $login = $parameters[ $prefix . 'smartcat_api_login' ] ) || empty( $password = $parameters[ $prefix . 'smartcat_api_password' ] ) ) {
			$ajax_response->send_error( __( 'Login and password are required', 'translation-connectors' ), $data );
		}

		$server = $parameters[ $prefix . 'smartcat_api_server' ];

		if ( '******' === $password ) {
			$password = $options->get_and_decrypt( 'smartcat_api_password' );
		}

		// Testing login to Smartcat.
		$account_info = null;
		try {
			$api          = new SmartCAT( $login, $password, $server );
			$account_info = $api->getAccountManager()->accountGetAccountInfo();
		} catch ( \Exception $e ) {
			$ajax_response->send_error( __( 'Invalid username or password', 'translation-connectors' ), $data );
		}

		try {
			$utils::check_vendor_exists( $api );
		} catch ( \Exception $e ) {
			$ajax_response->send_error( $e->getMessage(), $data );
		}

		try {
			$cron = new CronHelper();
			if ( ! ( boolval( $parameters[ $prefix . 'use_external_cron' ] ) && boolval( $options->get( 'use_external_cron' ) ) ) ) {
				if ( $parameters[ $prefix . 'use_external_cron' ] ) {
					$cron->register();
					Utils::disable_system_cron();
				} else {
					$cron->unregister();
					Utils::enable_system_cron();
				}
			}
		} catch ( \Exception $e ) {
			Logger::error("external cron", "External cron cause error: '{$e->getMessage()}'");
			$ajax_response->send_error( $e->getMessage(), $data );
		}

		if ( $account_info && $account_info->getName() ) {
			$options->set( 'smartcat_account_name', $account_info->getName() );
		}

		$ajax_response->send_success( __( 'Settings successfully saved', 'translation-connectors' ), $data );
	}

	public static function refresh_translation() {
		$ajax_response        = new AjaxResponse();
		$statistic_repository = null;

		$verify_nonce = wp_verify_nonce(
			wp_unslash( sanitize_key( $_POST['_wpnonce'] ?? null ) ),
			'bulk-statistics'
		);

		if ( ! current_user_can( 'publish_posts' ) || ! $verify_nonce ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
		}

		$data      = [ 'isActive' => SmartCAT::is_active() ];
		$container = self::get_container();
		$post      = sanitize_post( $_POST );

		try {
			/** @var StatisticRepository $statistic_repository */
			$statistic_repository = $container->get( 'entity.repository.statistic' );
		} catch ( \Exception $e ) {
			Logger::error( 'Can\'t get container', "Reason: {$e->getMessage()} {$e->getTraceAsString()}" );
			$ajax_response->send_error( $e->getMessage(), [], 400 );
		}

		if ( ! empty( $post['stat_id'] ) && intval( $post['stat_id'] ) ) {
			$statistic = $statistic_repository->get_one_by_id( intval( $post['stat_id'] ) );
			if ( $statistic->get_target_post_id() ) {
				$statistic->set_status( Statistics::STATUS_SENDED );
				$statistic_repository->update( $statistic );

				$data['statistic'] = [
					'status' => __( 'In progress', 'translation-connectors' ),
				];

				$ajax_response->send_success( __( 'Items have been successfully sent on update.', 'translation-connectors' ), $data );
			}
		}

		$ajax_response->send_error( __( 'Incorrect request', 'translation-connectors' ), $data );
	}

	public static function synchronize() {
		$ajax_response        = new AjaxResponse();
		$statistic_repository = null;

		$verify_nonce = wp_verify_nonce(
			wp_unslash( sanitize_key( $_POST['_wpnonce'] ?? null ) ),
			'bulk-statistics'
		);

		if ( ! current_user_can( 'publish_posts' ) || ! $verify_nonce ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
		}

		if ( spawn_cron() ) {
			$ajax_response->send_success( __( 'Cron successfully spawned', 'translation-connectors' ), [] );
		}

		$ajax_response->send_error( __( 'Cron can\'t be spawned at the moment. Cron start minimal interval exceeded.', 'translation-connectors' ), [] );
	}

	/**
	 *
	 */
	public static function create_profile() {
		$ajax_response = new AjaxResponse();
		$verify_nonce  = wp_verify_nonce(
			wp_unslash( sanitize_key( $_POST['sc_profile_wpnonce'] ?? null ) ),
			'sc_profile_edit'
		);

		if ( ! current_user_can( 'publish_posts' ) || ! $verify_nonce ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
		}

		$data          = sanitize_post( $_POST, 'db' );
		$profiles_repo = null;
		$smartcat      = null;
		$container     = self::get_container();
		Connector::set_core_parameters();

		try {
			/** @var ProfileRepository $profiles_repo */
			$profiles_repo = $container->get( 'entity.repository.profile' );
			$smartcat      = self::get_smartcat();
		} catch ( \Exception $e ) {
			Logger::error( 'Can\'t get container', "Reason: {$e->getMessage()} {$e->getTraceAsString()}" );
			$ajax_response->send_error( $e->getMessage(), [], 400 );
		}

		if ( ! empty( $data['profile_id'] ) ) {
			$profile = $profiles_repo->get_one_by_id( $data['profile_id'] );
		} else {
			$profile = new Profile();
		}

		if ( empty( $data['profile_name'] ) ) {
			$targets              = implode( ', ', $data['profile_target_langs'] );
			$data['profile_name'] = sprintf(  __( 'Translation: %s', 'translation-connectors' ), "{$data['profile_source_lang']} -> {$targets}" );
		}

		$key = array_search( $data['profile_source_lang'], $data['profile_target_langs'], true );
		if ( false !== $key ) {
			unset( $data['profile_target_langs'][ $key ] );
		}

		try {
			if ( ! empty( $data['profile_project_id'] ) ) {
				$sc_project = $smartcat->getProjectManager()->projectGet( $data['profile_project_id'] );
				$sc_project->getId();
			}
		} catch ( \Exception $e ) {
			$ajax_response->send_error(
				sprintf( __( 'Project "%s" does not exists', 'translation-connectors' ), $data['profile_project_id'] ),
				[],
				404
			);
		}

		if ( empty( $data['profile_target_langs'] ) ) {
			$ajax_response->send_error( __( 'Source language and target language are the same. Please select different languages.', 'translation-connectors' ), [], 400 );
		}

		// Check language-pairs in project and profile
		try {
			$languageConverter = $container->get( 'language.converter' );

			if ( ! empty( $data['profile_project_id'] ) && !empty( $languageConverter ) ) {
				$sc_project = $smartcat->getProjectManager()->projectGet( $data['profile_project_id'] );
				$projectSourceLanguage = $sc_project->getSourceLanguage();
				$projectTargetLanguages = $sc_project->getTargetLanguages();
				$profileSourceLanguage = $languageConverter->get_sc_code_by_wp($data['profile_source_lang'])->get_sc_code();
				$profileTargetLanguages = array_map( function ( $profileTargetLanguage ) use ( $languageConverter ) {
					return $languageConverter->get_sc_code_by_wp( $profileTargetLanguage )->get_sc_code();
				}, $data['profile_target_langs'] );
				asort( $projectTargetLanguages );
				asort( $profileTargetLanguages );

				$profileTranslationDirection = $profileSourceLanguage . ' => ' . join( ", ", $profileTargetLanguages );
				$projectTranslationDirection = $projectSourceLanguage . ' => ' . join( ", ", $projectTargetLanguages );

				if ( $profileTranslationDirection !== $projectTranslationDirection ) {
					$ajax_response->send_error(
						sprintf( __( 'You cannot save the profile, because the direction of the translation in the profile (%s) is different from the direction of the translation in the project you specified (%s)', 'translation-connectors' ), $profileTranslationDirection, $projectTranslationDirection ),
						[],
						400
					);
				}
			}
		} catch ( \Exception $e ) {
			$ajax_response->send_error(
				sprintf( __( 'Error while checking language-pairs: "%s"', 'translation-connectors' ), $e->getMessage() ),
				[],
				400
			);
		}

		$profile
			->set_name( $data['profile_name'] )
			->set_vendor( $data['profile_vendor'] )
			->set_vendor_name( __( 'Translate internally', 'translation-connectors' ) )
			->set_source_language( $data['profile_source_lang'] )
			->set_target_languages( $data['profile_target_langs'] )
			->set_project_id( $data['profile_project_id'] )
			->set_workflow_stages( $data['profile_workflow_stages'] )
			->set_auto_update( $data['profile_auto_update'] ?? false )
			->set_auto_send( $data['profile_auto_send'] ?? false );

		try {
			$vendor_list = wp_cache_get( 'vendor_list', 'translation-connectors' );

			if ( ! $vendor_list ) {
				$vendor_list = $smartcat->getDirectoriesManager()->directoriesGet( [ 'type' => 'vendor' ] )->getItems();
				wp_cache_set( 'vendor_list', $vendor_list, 'translation-connectors', 3600 );
			}

			foreach ( $vendor_list as $vendor ) {
				if ( $data['profile_vendor'] === $vendor->getId() ) {
					$profile->set_vendor_name( $vendor->getName() );
				}
			}
		} catch ( \Exception $e ) {
			Logger::warning( "Can't set vendor name", "Reason: {$e->getMessage()}" );
		}

		try {
			$result = $profiles_repo->save( $profile );
		} catch ( \Exception $e ) {
			Logger::error( 'Can\'t save profile', "Reason: {$e->getMessage()} {$e->getTraceAsString()}" );
			$result = false;
		}

		if ( $result ) {
			$ajax_response->send_success( __( 'Item successfully created', 'translation-connectors' ), $data );
		} else {
			$ajax_response->send_error( __( 'Item was not created', 'translation-connectors' ), $data );
		}
	}

	/**
	 *
	 */
	public static function delete_profile() {
		$ajax_response = new AjaxResponse();

		$verify_nonce = wp_verify_nonce(
			wp_unslash( sanitize_key( $_POST['_wpnonce'] ?? null ) ),
			'bulk-profiles'
		);

		if ( ! current_user_can( 'publish_posts' ) || ! $verify_nonce ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
		}

		$profiles_repo = null;
		$container     = self::get_container();
		$data          = sanitize_post( $_POST, 'db' );

		try {
			/** @var ProfileRepository $profiles_repo */
			$profiles_repo = $container->get( 'entity.repository.profile' );
		} catch ( \Exception $e ) {
			Logger::error( 'Can\'t get container', "Reason: {$e->getMessage()} {$e->getTraceAsString()}" );
			$ajax_response->send_error( $e->getMessage(), [], 400 );
		}

		if ( $data['profile_id'] ) {
			if ( $profiles_repo->delete_by_id( $data['profile_id'] ) ) {
				$ajax_response->send_success( __( 'Item successfully deleted', 'translation-connectors' ), $data );
			}
		}

		$ajax_response->send_error( __( 'Incorrect request', 'translation-connectors' ), [], 400 );
	}

	/**
	 *
	 */
	public static function delete_statistics() {
		$ajax_response = new AjaxResponse();

		$verify_nonce = wp_verify_nonce(
			wp_unslash( sanitize_key( $_POST['_wpnonce'] ?? null ) ),
			'bulk-statistics'
		);

		if ( ! current_user_can( 'publish_posts' ) || ! $verify_nonce ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
		}

		$statistics_repo = null;
		$container       = self::get_container();
		$data            = sanitize_post( $_POST, 'db' );

		try {
			/** @var StatisticRepository $statistics_repo */
			$statistics_repo = $container->get( 'entity.repository.statistic' );
		} catch ( \Exception $e ) {
			Logger::error( 'Can\'t get container', "Reason: {$e->getMessage()} {$e->getTraceAsString()}" );
			$ajax_response->send_error( $e->getMessage(), [], 400 );
		}

		if ( $data['stat_id'] ) {
			if ( $statistics_repo->delete_by_id( $data['stat_id'] ) ) {
				$ajax_response->send_success( __( 'Item successfully deleted', 'translation-connectors' ), $data );
			}
		}

		$ajax_response->send_error( __( 'Incorrect request', 'translation-connectors' ), [], 400 );
	}

	/**
	 *
	 */
	public static function cancel_statistics() {
		$ajax_response = new AjaxResponse();

		$verify_nonce = wp_verify_nonce(
			wp_unslash( sanitize_key( $_POST['_wpnonce'] ?? null ) ),
			'bulk-statistics'
		);

		if ( ! current_user_can( 'publish_posts' ) || ! $verify_nonce ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
		}

		$statistics_repo = null;
		$container       = self::get_container();
		$data            = sanitize_post( $_POST, 'db' );

		try {
			/** @var StatisticRepository $statistics_repo */
			$statistics_repo = $container->get( 'entity.repository.statistic' );
		} catch ( \Exception $e ) {
			Logger::error( 'Can\'t get container', "Reason: {$e->getMessage()} {$e->getTraceAsString()}" );
			$ajax_response->send_error( $e->getMessage(), [], 400 );
		}

		if ( $data['stat_id'] ) {
			$statistic = $statistics_repo->get_one_by_id( $data['stat_id'] );

			if ( ! in_array( $statistic->get_status(), [ Statistics::STATUS_COMPLETED, Statistics::STATUS_FAILED, Statistics::STATUS_CANCELED ], true ) ) {
				$statistic->set_status( Statistics::STATUS_CANCELED );
				if ( $statistics_repo->save( $statistic ) ) {
					$data['statistic'] = [
						'status' => __( 'Canceled', 'translation-connectors' ),
					];
					$ajax_response->send_success( __( 'Item was successfully canceled', 'translation-connectors' ), $data );
				}
			}
		}

		$ajax_response->send_error( __( 'Incorrect request', 'translation-connectors' ), [], 400 );
	}

	/**
	 * Create tasks for smartcat
	 */
	public static function send_to_smartcat() {
		$ajax_response         = new AjaxResponse();
		$task_repository       = null;
		$statistics_repository = null;
		$profiles_repository   = null;

		$verify_nonce = wp_verify_nonce(
			wp_unslash( sanitize_key( $_POST['sc_send_nonce'] ?? null ) ),
			'sc_send_nonce'
		);

		if ( ! current_user_can( 'publish_posts' ) || ! $verify_nonce ) {
			$ajax_response->send_error( __( 'Access denied', 'translation-connectors' ), [], 403 );
		}

		$post = sanitize_post( $_POST );

		$container = self::get_container();
		try {
			/** @var TaskRepository $task_repository */
			$task_repository = $container->get( 'entity.repository.task' );
			/** @var StatisticRepository $statistics_repository */
			$statistics_repository = $container->get( 'entity.repository.statistic' );
			/** @var ProfileRepository $profiles_repository */
			$profiles_repository = $container->get( 'entity.repository.profile' );
		} catch ( \Exception $e ) {
			Logger::error( 'Can\'t get container', "Reason: {$e->getMessage()} {$e->getTraceAsString()}" );
			$ajax_response->send_error( $e->getMessage(), [], 400 );
		}

		$posts   = explode( ',', $post['posts'] );
		$profile = $profiles_repository->get_one_by_id( $post['sc-profile'] );

		if ( empty( $posts ) || empty( $profile ) ) {
			$ajax_response->send_error( __( 'Incorrect request', 'translation-connectors' ), [], 403 );
		}

		$task = new Task();
		$task->set_source_language( $profile->get_source_language() )
			->set_target_languages( $profile->get_target_languages() )
			->set_profile_id( $profile->get_id() )
			->set_vendor_id( $profile->get_vendor() ?? null )
			->set_workflow_stages( $profile->get_workflow_stages() )
			->set_project_id( $profile->get_project_id() ?? null );

		$task_id = $task_repository->add( $task );

		foreach ( $posts as $post_id ) {
			if ( $task_id ) {
				$stat = new Statistics();
				$stat->set_task_id( $task_id )
					->set_post_id( $post_id )
					->set_source_language( $profile->get_source_language() )
					->set_progress( 0 )
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
					$ajax_response->send_error( __( 'Not all items was created', 'translation-connectors' ) );
				}
			} else {
				$data['task'] = $task;
				$ajax_response->send_error( __( 'Item was not created', 'translation-connectors' ), $data );
			}
		}

		spawn_cron();

		if ( $task_id ) {
			$ajax_response->send_success( __( 'Items have been successfully sent.', 'translation-connectors' ), $data );
		} else {
			$ajax_response->send_error( __( 'Item was not created', 'translation-connectors' ), $data );
		}
	}

	/**
	 * Register hooks function
	 */
	public function register_hooks() {
		if ( wp_doing_ajax() ) {
			$container = self::get_container();
			$prefix    = $container->getParameter( 'plugin.table.prefix' );

			add_action( "wp_ajax_{$prefix}validate_settings", [ self::class, 'validate_settings' ] );
			add_action( "wp_ajax_{$prefix}delete_profile", [ self::class, 'delete_profile' ] );
			add_action( "wp_ajax_{$prefix}create_profile", [ self::class, 'create_profile' ] );
			add_action( "wp_ajax_{$prefix}cancel_statistics", [ self::class, 'cancel_statistics' ] );
			add_action( "wp_ajax_{$prefix}delete_statistics", [ self::class, 'delete_statistics' ] );
			add_action( "wp_ajax_{$prefix}send_to_smartcat", [ self::class, 'send_to_smartcat' ] );
			add_action( "wp_ajax_{$prefix}refresh_translation", [ self::class, 'refresh_translation' ] );
			add_action( "wp_ajax_{$prefix}synchronize", [ self::class, 'synchronize' ] );
		}
	}

	/**
	 * Get Smartcat client service
	 * WTF?! Why I can't use Connector::set_core_parameters WordPress?! TELL ME!!!
	 *
	 * @return SmartCAT|null
	 * @throws \Exception
	 */
	private static function get_smartcat() {
		$container = self::get_container();

		try {
			$options = $container->get( 'core.options' );
			$container->setParameter( 'smartcat.api.login', $options->get_and_decrypt( 'smartcat_api_login' ) );
			$container->setParameter( 'smartcat.api.password', $options->get_and_decrypt( 'smartcat_api_password' ) );
			$container->setParameter( 'smartcat.api.server', $options->get( 'smartcat_api_server' ) );
		} catch ( \Exception $e ) {
			Logger::warning( "Can't set core parameters", "Reason: {$e->getMessage()} {$e->getTraceAsString()}" );
		}

		return $container->get( 'smartcat' );
	}
}
