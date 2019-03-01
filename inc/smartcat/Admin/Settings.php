<?php

namespace SmartCAT\WP\Admin;

use SmartCAT\WP\Connector;
use SmartCAT\WP\DITrait;
use SmartCAT\WP\Helpers\Cryptographer;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\Helpers\TemplateEngine;
use SmartCAT\WP\WP\InitInterface;
use SmartCAT\WP\WP\Options;

/**
 * Класс для инкапсуляции настроек и опций
 */
final class Settings implements InitInterface {
	use DITrait;

	static function add_admin_menu() {
		add_menu_page(
			__( 'Localization', 'translation-connectors' ),
			__( 'Localization', 'translation-connectors' ),
			'edit_pages',
			'sc-settings',
			[ self::class, 'render_settings_page' ],
			'dashicons-translation'
		);

		add_submenu_page( 'sc-settings', __( 'Settings', 'translation-connectors' ),
			__( 'Settings', 'translation-connectors' ),
			'edit_pages',
			'sc-settings', [ self::class, 'render_settings_page' ] );
		add_submenu_page( 'sc-settings', __( 'Dashboard', 'translation-connectors' ),
			__( 'Dashboard', 'translation-connectors' ),
			'edit_pages',
			'sc-translation-progress', [ self::class, 'render_progress_page' ] );
	}

	static function make_settings_page() {
		$container = self::get_container();
		$prefix    = $container->getParameter( 'plugin.table.prefix' );

		$server          = $prefix . 'smartcat_api_server';
		$login           = $prefix . 'smartcat_api_login';
		$password        = $prefix . 'smartcat_api_password';
		$project_id      = $prefix . 'smartcat_api_project_id';
		$workflow_stages = $prefix . 'smartcat_workflow_stages';
		$vendor_id       = $prefix . 'smartcat_vendor_id';
		$auto_send_on_update = $prefix . 'smartcat_auto_send_on_update';

		//общая регистрация параметров
		register_setting( 'smartcat', $server, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $login, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $password, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $project_id, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $workflow_stages, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $vendor_id, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $auto_send_on_update, [ 'type' => 'bool' ] );

		//секции
		add_settings_section(
			'smartcat_required',
			__( 'Required settings', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'dummy_callback' ],
			'smartcat'
		);

		add_settings_section(
			'smartcat_additional',
			__( 'Additional settings', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'dummy_callback' ],
			'smartcat'
		);

		//привязка параметров к секциям
		add_settings_field(
			$server,
			__( 'API server', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'select_callback' ],
			'smartcat',
			'smartcat_required',
			[
				'label_for'      => $server,
				'option_name'    => $server,
				'select_options' => [
					SmartCAT::SC_EUROPE => __( 'Europe', 'translation-connectors' ),
					SmartCAT::SC_USA    => __( 'USA', 'translation-connectors' ),
					SmartCAT::SC_ASIA   => __( 'Asia', 'translation-connectors' )
				]
			]
		);

		add_settings_field(
			$login,
			__( 'API login', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'input_text_callback' ],
			'smartcat',
			'smartcat_required',
			[ 'label_for' => $login, 'option_name' => $login ]
		);

		add_settings_field(
			$password,
			__( 'API password', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'input_text_callback' ],
			'smartcat',
			'smartcat_required',
			[
				'label_for'   => $password,
				'option_name' => $password,
				'type'        => 'password'
			]
		);

		add_settings_field(
			$workflow_stages,
			__( 'Workflow stages', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'input_checkbox_callback' ],
			'smartcat',
			'smartcat_additional',
			[
				'label_for'       => $workflow_stages,
				'option_name'     => $workflow_stages,
				'checkboxes_list' => [
					'Translation'  => __( 'Translation', 'translation-connectors' ),
					'Editing'      => __( 'Editing', 'translation-connectors' ),
					'Proofreading' => __( 'Proofreading', 'translation-connectors' )
				]
			]
		);

		add_settings_field(
			$project_id,
			__( 'Project id', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'input_text_callback' ],
			'smartcat',
			'smartcat_additional',
			[ 'label_for' => $project_id, 'option_name' => $project_id ]
		);

		// Disable feature
		/* add_settings_field(
			$auto_send_on_update,
			__( 'Auto send posts on update', 'translation-connectors' ),
			[ self::class, 'input_checkbox_callback' ],
			'smartcat',
			'smartcat_additional',
			[ 'label_for' => $auto_send_on_update, 'option_name' => $auto_send_on_update, 'checkboxes_list' => [
				1 => ''
			] ]
		); */

		$select_array = [];
		if ( SmartCAT::is_active() ) {
			//TODO: добавить кэш?
			/** @var Options $options */
			$options  = $container->get( 'core.options' );
			$login    = $options->get_and_decrypt( 'smartcat_api_login' );
			$password = $options->get_and_decrypt( 'smartcat_api_password' );
			$server   = $options->get( 'smartcat_api_server' );

			$sc = new SmartCAT( $login, $password, $server );
			try {
				$vendors = $sc->getDirectoriesManager()->directoriesGet( [ 'type' => 'vendor' ] );
				$items   = $vendors->getItems();
				foreach ( $items as $item ) {
					$select_array[ $item->getId() ] = $item->getName();
				}

			} catch ( \Exception $e ) {
				if ( $e->getMessage() == 'Invalid username or password' ) {
					$options->set('smartcat_api_login', null);
					$options->set('smartcat_api_password', null);
					Connector::set_core_parameters();
				}
			}

			if ( ! count( $select_array ) ) {
				//$select_array = [ __( 'You haven\'t got vendors', 'translation-connectors' ) ];
			} else {
				array_unshift( $select_array, __( 'Please select your vendor', 'translation-connectors' ) );
				add_settings_field(
					$vendor_id,
					__( 'Vendor ID', 'translation-connectors' ),
					[ FrontendCallbacks::class, 'select_callback' ],
					'smartcat',
					'smartcat_additional',
					[
						'label_for'      => $vendor_id,
						'option_name'    => $vendor_id,
						'select_options' => $select_array
					]
				);
			}
		}
	}

	static function render_settings_page() {
		$container = self::get_container();
		/** @var TemplateEngine $render */
		$render = $container->get('templater');

		echo $render->render('settings', [
			'title' => $GLOBALS['title'],
			'saved' => isset( $_GET['settings-updated'] ),
			'message' => __( 'Settings saved.' ),
			'fields' => function () use ($render) {
				return $render->ob_to_string('settings_fields', 'smartcat');
			},
			'sections' => function () use ($render) {
			    return $render->ob_to_string('do_settings_sections', 'smartcat');
			},
			'save_changes' => __( 'Save Changes' )
		]);
	}

	static function render_progress_page() {
		$container = self::get_container();
		/** @var TemplateEngine $render */
		$render = $container->get('templater');
		$statistics_repository = $container->get( 'entity.repository.statistic' );
		$options = $container->get( 'core.options' );

		$limit = 100;

		$statistics_table = new StatisticsTable();
		$max_page = ceil( $statistics_repository->get_count() / $limit );
		$page = self::get_page($max_page);
		$is_statistics_queue_active = boolval( $options->get( 'statistic_queue_active' ) );
		$statistics_result = $statistics_repository->get_statistics( $limit * ( $page - 1 ), $limit );

		echo $render->render('dashboard', [
			'title' => $GLOBALS['title'],
			'button_status' => $is_statistics_queue_active ? 'disabled="disabled"' : false,
			'statistic_result' => $statistics_result ? true : false,
			'refresh_text' => __( 'Refresh statistics', 'translation-connectors' ),
			'statistic_table' => function () use ($statistics_table, $render, $statistics_result) {
				$table_with_data = $statistics_table->set_data( $statistics_result );
				return 	$render->ob_to_string([$table_with_data, 'display']);
			},
			'pages_text' => __( 'Pages', 'translation-connectors' ),
			'empty_message' => __( 'Statistics is empty', 'translation-connectors' ),
			'paginator' => function () use ($max_page, $page) {
				return self::get_paginator_code($max_page, $page);
			}
		]);
	}

	static private function get_paginator_code($max_page, $page)
	{
		$url = strtok( $_SERVER['REQUEST_URI'], '?' );

		$paginator = '';

		for ( $page_number = 1; $page_number <= $max_page; $page_number ++ ) {
			if ( $page_number == $page ) {
				$paginator .= "<span>{$page_number}</span>";
			} else {
				$full_url = esc_html( $url . '?page=sc-translation-progress&page-number=' . $page_number );
				$paginator .= '<a href="' . $full_url . '">' . $page_number . '</a>';
			}
		}

		return $paginator;
	}

	static private function get_page($max_page)
	{
		$page = isset( $_GET['page-number'] ) ? abs( intval( $_GET['page-number'] ) ) : 1;
		$page = ( $page > $max_page ) ? $max_page : $page;
		$page = ( $page >= 1 ) ? $page : 1;

		return $page;
	}

	public function plugin_init() {
		self::apply_filters_to_settings();
	}

	static function pre_update_password( $new_value ) {
		if ( $new_value == '******' ) {
			$container = self::get_container();
			/** @var Options $options */
			$options = $container->get( 'core.options' );

			$new_value = $options->get_and_decrypt( 'smartcat_api_password' );
		}

		return Cryptographer::encrypt( $new_value );
	}

	static function pre_update_vendor_id( $new_value ) {
		if ( $new_value === 0 ) {
			return null;
		}

		return $new_value;
	}

	static function apply_filters_to_settings() {
		$container = self::get_container();
		$prefix    = $container->getParameter( 'plugin.table.prefix' );

		add_filter( "pre_update_option_{$prefix}smartcat_vendor_id", [ self::class, 'pre_update_vendor_id' ] );

		//шифруем логин и пароль при записи
		add_filter( "pre_update_option_{$prefix}smartcat_api_login", [ Cryptographer::class, 'encrypt' ] );
		add_filter( "pre_update_option_{$prefix}smartcat_api_password", [ self::class, 'pre_update_password' ] );
		//дешифруем при получении
		add_filter( "option_{$prefix}smartcat_api_login", [ Cryptographer::class, 'decrypt' ] );
		add_filter( "option_{$prefix}smartcat_api_password", [ Cryptographer::class, 'decrypt' ] );
	}
}