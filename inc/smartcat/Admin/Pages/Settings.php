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

namespace SmartCAT\WP\Admin\Pages;

use SmartCAT\WP\Admin\FrontendCallbacks;
use SmartCAT\WP\Helpers\Cryptographer;
use SmartCAT\WP\Helpers\SmartCAT;

/**
 * Class Settings
 *
 * @package SmartCAT\WP\Admin\Pages
 */
class Settings extends PageAbstract {
	/**
	 * Render settings page
	 */
	public static function render() {
		$renderer = self::get_renderer();

		echo $renderer->render(
			'settings',
			[
				'texts'             => self::get_texts(),
				'saved'             => isset( $_GET['settings-updated'] ),
				'show_regform'      => self::is_need_show_regform(),
				'fields'            => $renderer->ob_to_string( 'settings_fields', 'smartcat' ),
				'sections'          => $renderer->ob_to_string( 'do_settings_sections', 'smartcat' ),
				'sc_validate_nonce' => wp_create_nonce( 'sc_validate_settings' ),
			]
		);
	}

	/**
	 * Get texts array for render
	 *
	 * @return array
	 */
	private static function get_texts() {
		return [
			'title'        => $GLOBALS['title'],
			'message'      => __( 'Settings saved.' ),
			'save_changes' => __( 'Save Changes' ),
		];
	}

	/**
	 * @throws \Exception
	 */
	public static function make_page() {
		$container      = self::get_container();
		$prefix         = $container->getParameter( 'plugin.table.prefix' );
		$server         = $prefix . 'smartcat_api_server';
		$login          = $prefix . 'smartcat_api_login';
		$password       = $prefix . 'smartcat_api_password';
		$debug_mode     = $prefix . 'smartcat_debug_mode';
		$events_enabled = $prefix . 'smartcat_events_enabled';
		$external_cron  = $prefix . 'use_external_cron';

		register_setting( 'smartcat', $server, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $login, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $password, [ 'type' => 'string' ] );
		register_setting( 'smartcat', $debug_mode, [ 'type' => 'bool' ] );
		register_setting( 'smartcat', $events_enabled, [ 'type' => 'bool' ] );
		register_setting( 'smartcat', $external_cron, [ 'type' => 'bool' ] );

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
			__( 'Server', 'translation-connectors' ),
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
			__( 'Account ID', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'input_text_callback' ],
			'smartcat',
			'smartcat_required',
			[
				'label_for'   => $login,
				'option_name' => $login,
			]
		);

		add_settings_field(
			$password,
			__( 'API key', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'input_text_callback' ],
			'smartcat',
			'smartcat_required',
			[
				'label_for'   => $password,
				'option_name' => $password,
				'type'        => 'password',
				'hint'        => '<a href="https://help.smartcat.ai/hc/en-us/articles/115002475012" target="_blank">'
								. __( 'Learn more', 'translation-connectors' ) . '</a> '
								. __( 'on how to get Smartcat API credentials.', 'translation-connectors' ),
			]
		);

		add_settings_field(
			$debug_mode,
			__( 'Debug mode', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'input_checkbox_callback' ],
			'smartcat',
			'smartcat_additional',
			[
				'label_for'       => $debug_mode,
				'option_name'     => $debug_mode,
			]
		);

		add_settings_field(
			$events_enabled,
			__( 'Enable events log', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'input_checkbox_callback' ],
			'smartcat',
			'smartcat_additional',
			[
				'label_for'       => $events_enabled,
				'option_name'     => $events_enabled,
			]
		);

		add_settings_field(
			$external_cron,
			__( 'Use external cron', 'translation-connectors' ),
			[ FrontendCallbacks::class, 'input_checkbox_callback' ],
			'smartcat',
			'smartcat_additional',
			[
				'label_for'       => $external_cron,
				'option_name'     => $external_cron,
			]
		);
	}

	/**
	 * @param $new_value
	 *
	 * @return string
	 */
	public static function pre_update_password( $new_value ) {
		if ( '******' === $new_value ) {
			$options = self::get_options();

			$new_value = $options->get_and_decrypt( 'smartcat_api_password' );
		}

		return Cryptographer::encrypt( $new_value );
	}

	/**
	 * Apply filters
	 */
	public static function apply_filters_to_settings() {
		$container = self::get_container();
		$prefix    = $container->getParameter( 'plugin.table.prefix' );

		add_filter( "pre_update_option_{$prefix}smartcat_api_login", [ Cryptographer::class, 'encrypt' ] );
		add_filter( "pre_update_option_{$prefix}smartcat_api_password", [ self::class, 'pre_update_password' ] );
		add_filter( "option_{$prefix}smartcat_api_login", [ Cryptographer::class, 'decrypt' ] );
		add_filter( "option_{$prefix}smartcat_api_password", [ Cryptographer::class, 'decrypt' ] );
	}
}
