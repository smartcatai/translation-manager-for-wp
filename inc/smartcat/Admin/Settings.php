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

use SmartCAT\WP\Admin\Pages\Dashboard;
use SmartCAT\WP\Admin\Pages\Errors;
use SmartCAT\WP\Admin\Pages\Profiles;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DITrait;
use SmartCAT\WP\Helpers\Cryptographer;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\Helpers\TemplateEngine;
use SmartCAT\WP\WP\InitInterface;
use SmartCAT\WP\WP\Options;

/**
 * Class Settings
 *
 * @package SmartCAT\WP\Admin
 */
final class Settings implements InitInterface {
	use DITrait;

	/**
	 * Render menu in sidebar
	 */
	public static function add_admin_menu() {
		add_menu_page(
			__( 'Localization', 'translation-connectors' ),
			__( 'Localization', 'translation-connectors' ),
			'edit_pages',
			'sc-dashboard',
			[ Dashboard::class, 'render' ],
			'dashicons-translation'
		);

		add_submenu_page(
			'sc-dashboard',
			__( 'Dashboard', 'translation-connectors' ),
			__( 'Dashboard', 'translation-connectors' ),
			'edit_pages',
			'sc-dashboard',
			[ Dashboard::class, 'render' ]
		);

		add_submenu_page(
			'sc-dashboard',
			__( 'Profiles', 'translation-connectors' ),
			__( 'Profiles', 'translation-connectors' ),
			'edit_pages',
			'sc-profiles',
			[ Profiles::class, 'render' ]
		);

		add_submenu_page(
			'sc-dashboard',
			__( 'Errors', 'translation-connectors' ),
			__( 'Errors', 'translation-connectors' ),
			'edit_pages',
			'sc-errors',
			[ Errors::class, 'render' ]
		);

		add_submenu_page(
			'sc-dashboard',
			__( 'Settings', 'translation-connectors' ),
			__( 'Settings', 'translation-connectors' ),
			'edit_pages',
			'sc-settings',
			[ self::class, 'render_settings_page' ]
		);
	}

	/**
	 * @throws \Exception
	 */
	public static function make_settings_page() {
		$container           = self::get_container();
		$prefix              = $container->getParameter( 'plugin.table.prefix' );
		$server              = $prefix . 'smartcat_api_server';
		$login               = $prefix . 'smartcat_api_login';
		$password            = $prefix . 'smartcat_api_password';
		$project_id          = $prefix . 'smartcat_api_project_id';
		$workflow_stages     = $prefix . 'smartcat_workflow_stages';
		$vendor_id           = $prefix . 'smartcat_vendor_id';
		$auto_send_on_update = $prefix . 'smartcat_auto_send_on_update';

		register_setting( 'smartcat', $server, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $login, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $password, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $project_id, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $workflow_stages, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $vendor_id, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $auto_send_on_update, [ 'type' => 'bool' ] );

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
					SmartCAT::SC_ASIA   => __( 'Asia', 'translation-connectors' ),
				],
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
				'type'		=> 'password'
			]
		 );

		add_settings_field(
			$workflow_stages,
			__( 'Workflow stages', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'input_checkbox_callback' ],
			'smartcat',
			'smartcat_additional',
			[
				'label_for'	   => $workflow_stages,
				'option_name'	 => $workflow_stages,
				'checkboxes_list' => [
					'Translation'  => __( 'Translation', 'translation-connectors' ),
					'Editing'	  => __( 'Editing', 'translation-connectors' ),
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
			$login	= $options->get_and_decrypt( 'smartcat_api_login' );
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
					$options->set( 'smartcat_api_login', null );
					$options->set( 'smartcat_api_password', null );
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
						'label_for'	     => $vendor_id,
						'option_name'	 => $vendor_id,
						'select_options' => $select_array
					]
				);
			}
		}
	}

	static function render_settings_page() {
		$isCookie = isset( $_COOKIE['regform'] );
		$container = self::get_container();
		/** @var TemplateEngine $render */
		$render = $container->get( 'templater' );

		add_thickbox();

		echo $render->render( 'settings', [
			'title' => $GLOBALS['title'],
			'isCookie' => $isCookie,
			'saved' => isset( $_GET['settings-updated'] ),
			'message' => __( 'Settings saved.' ),
			'fields' => function () use ( $render ) {
				return $render->ob_to_string( 'settings_fields', 'smartcat' );
			},
			'sections' => function () use ( $render ) {
				return $render->ob_to_string( 'do_settings_sections', 'smartcat' );
			},
			'save_changes' => __( 'Save Changes' )
		] );
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

	static function apply_filters_to_settings()
	{
		$container = self::get_container();
		$prefix	= $container->getParameter( 'plugin.table.prefix' );

		add_filter( "pre_update_option_{$prefix}smartcat_vendor_id", [ self::class, 'pre_update_vendor_id' ] );

		//шифруем логин и пароль при записи
		add_filter( "pre_update_option_{$prefix}smartcat_api_login", [ Cryptographer::class, 'encrypt' ] );
		add_filter( "pre_update_option_{$prefix}smartcat_api_password", [ self::class, 'pre_update_password' ] );
		//дешифруем при получении
		add_filter( "option_{$prefix}smartcat_api_login", [ Cryptographer::class, 'decrypt' ] );
		add_filter( "option_{$prefix}smartcat_api_password", [ Cryptographer::class, 'decrypt' ] );
	}
}