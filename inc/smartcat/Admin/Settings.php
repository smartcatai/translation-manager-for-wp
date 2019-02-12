<?php

namespace SmartCAT\WP\Admin;

use SmartCAT\WP\Connector;
use SmartCAT\WP\DITrait;
use SmartCAT\WP\Helpers\Cryptographer;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\WP\InitInterface;
use SmartCAT\WP\WP\Options;

/**
 * Класс для инкапсуляции настроек и опций
 */
final class Settings implements InitInterface {
	use DITrait;

	static function add_admin_menu() {
		//add_options_page('Test Options', 'Test Options', 'edit_pages', 'testoptions', 'mt_options_page'); // это для меню "Настройки"
		//add_management_page('Test Manage', 'Test Manage', 'edit_pages', 'testmanage', 'mt_manage_page'); // для добавления в меню "Управление"

		add_menu_page(
			__( 'Localization', 'translation-connectors' ),
			__( 'Localization', 'translation-connectors' ),
			'edit_pages',
			'sc-settings',
			[ self::class, 'render_settings_page' ],
			'dashicons-translation'
		//plugins_url('/images/dashicon.png', SMARTCAT_PLUGIN_FILE)
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
			[ self::class, 'dummy_callback' ],
			'smartcat'
		);

		add_settings_section(
			'smartcat_additional',
			__( 'Additional settings', 'translation-connectors' ),
			[ self::class, 'dummy_callback' ],
			'smartcat'
		);

		//привязка параметров к секциям
		add_settings_field(
			$server,
			__( 'API server', 'translation-connectors' ),
			[ self::class, 'select_callback' ],
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
			[ self::class, 'input_text_callback' ],
			'smartcat',
			'smartcat_required',
			[ 'label_for' => $login, 'option_name' => $login ]
		);

		add_settings_field(
			$password,
			__( 'API password', 'translation-connectors' ),
			[ self::class, 'input_text_callback' ],
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
			[ self::class, 'input_checkbox_callback' ],
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
			[ self::class, 'input_text_callback' ],
			'smartcat',
			'smartcat_additional',
			[ 'label_for' => $project_id, 'option_name' => $project_id ]
		);

		add_settings_field(
			$auto_send_on_update,
			__( 'Auto send posts on update', 'translation-connectors' ),
			[ self::class, 'input_checkbox_callback' ],
			'smartcat',
			'smartcat_additional',
			[ 'label_for' => $auto_send_on_update, 'option_name' => $auto_send_on_update, 'checkboxes_list' => [
				1 => ''
			] ]
		);

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
					//die( 'Invalid username or password' );
				}
			}

			if ( ! count( $select_array ) ) {
				//$select_array = [ __( 'You haven\'t got vendors', 'translation-connectors' ) ];
			} else {
				array_unshift( $select_array, __( 'Please select your vendor', 'translation-connectors' ) );
				add_settings_field(
					$vendor_id,
					__( 'Vendor ID', 'translation-connectors' ),
					[ self::class, 'select_callback' ],
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

	static function select_callback( $args ) {
		$option = get_option( $args['option_name'] );
		echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option_name'] ) . '">';

		foreach ( $args['select_options'] as $select_option_value => $select_option_name ) {
			$escaped_select_option_value = esc_attr( $select_option_value );
			$select_option_name          = __( $select_option_name,
				'translation-connectors' ); //на всякий случай оборачиваю в локализацию
			echo "<option value='{$escaped_select_option_value}' "
			     . selected( $option, $select_option_value, false ) . ">{$select_option_name}</option>";
		}

		echo '</select>';
	}

	static function input_radio_callback( $args ) {
		//на случай добавления радиобаттонов, пока не используется
	}

	static function input_checkbox_callback( $args ) {
		$option_name = $args['option_name'];
		$option      = get_option( $option_name );
		if ( ! $option ) {
			$option = [ 'Translation' ];
		}
		foreach ( $args['checkboxes_list'] as $checkbox_value => $checkbox_text ) {
			$checkbox_text = __( $checkbox_text, 'translation-connectors' ); //на всякий случай оборачиваю в локализацию
			echo '<label for="' . esc_attr( $checkbox_value ) . '"><input name="'
			     . esc_attr( $option_name . '[]' )
			     . '" type="checkbox" id="' . esc_attr( $checkbox_value ) . '" value="' . esc_attr( $checkbox_value ) . '" '
			     . ( in_array( $checkbox_value, $option ) ? 'checked' : '' ) . '/>' . $checkbox_text . '</label>';
		}
	}

	static function input_text_callback( $args ) {
		$option_name = $args['option_name'];
		$option      = get_option( $option_name );
		$type        = isset( $args['type'] ) ? esc_attr( $args['type'] ) : 'text';

		if ( $type == 'password' && ! empty( $option ) ) {
			$option = '******';
		}

		echo '<input type="' . $type . '" id="' . esc_attr( $args['label_for'] )
		     . '" name="' . esc_attr( $option_name )
		     . '" value="' . esc_attr( $option ) . '"/>';
	}

	static function dummy_callback() {
		//используется в качестве коллбэка для секций
	}

	static function render_settings_page() {
		/** @noinspection PhpIncludeInspection */
		include SMARTCAT_PLUGIN_DIR . '/views/settings.php';
	}

	static function render_progress_page() {
		/** @noinspection PhpIncludeInspection */
		include SMARTCAT_PLUGIN_DIR . '/views/progress.php';
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