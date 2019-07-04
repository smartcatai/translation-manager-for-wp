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

namespace SmartCAT\WP\WP;

use SmartCAT\WP\Connector;

/**
 * Class Options
 * @package SmartCAT\WP\WP
 */
class Options implements PluginInterface {
	static private $options_list = null;
	private $prefix;

	/** @var  \SmartCAT\WP\Helpers\Cryptographer */
	private $cryptographer;

	public function __construct( $prefix ) {
		$this->prefix = $prefix;
		if ( self::$options_list === null ) {
			$options			= Connector::get_container()->getParameter( 'plugin.options' );
			self::$options_list = [];
			foreach ( $options as $option ) {
				self::$options_list[ $this->prefix . $option ] = $this->prefix . $option;
			}
		}
		if ( $this->cryptographer === null ) {
			$this->cryptographer = Connector::get_container()->get( 'cryptographer' );
		}
	}

	public function plugin_activate() {
	}

	public function plugin_deactivate() {
	}

	public function plugin_uninstall() {
		foreach ( self::$options_list as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Получает значение опции
	 *
	 * @param string $name
	 *
	 * @return mixed|bool
	 */
	public function get( $name ) {
		$system_name = "{$this->prefix}{$name}";
		//TODO: избавиться от ассертов
		assert( isset( self::$options_list[ $system_name ] ), "Неизвестная опция $name. Добавьте ее в plugin.options" );

		return get_option( $system_name );
	}

	/**
	 * Сохраняет значение опции
	 *
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function set( $name, $value ) {
		$systemName = "{$this->prefix}{$name}";
		assert( isset( self::$options_list[ $systemName ] ), "Неизвестная опция $name. Добавьте ее в plugin.options" );

		return update_option( $systemName, $value );
	}

	public function encrypt_AES( $txt ) {
		$cryptographer = $this->cryptographer;

		return $cryptographer::encrypt( $txt );
	}

	public function decrypt_AES( $txt ) {
		$cryptographer = $this->cryptographer;

		return $cryptographer::decrypt( $txt );
	}

	/**
	 * Получение значение зашифрованной текстовой опции
	 *
	 * @param $name
	 *
	 * @return string
	 */
	public function get_and_decrypt( $name ) {
		global $wp_filter;
		//благодаря магии хуков ВП логин и пароль разшифровываются и зашифровываются авоматом, и нам надо проверять активирован нужный нам хук или еще нет
		if ( ( $name == 'smartcat_api_login' || $name == 'smartcat_api_password' ) && ( ! empty( $wp_filter["option_{$this->prefix}$name"] ) ) ) {
			return $this->get( $name );
		} else {
			return $this->decrypt_AES( $this->get( $name ) );
		}
	}

	/**
	 * Зашифровывает и сохраняет значение текстовой опции
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return bool
	 */
	public function set_and_encrypt( $name, $value ) {
		//благодаря магии хуков ВП логин и пароль разшифровываются и зашифровываются авоматом, и нам надо проверять активирован нужный нам хук или еще нет
		if ( ( $name == 'smartcat_api_login' || $name == 'smartcat_api_password' ) && ( ! empty( $wp_filter["pre_update_option_{$this->prefix}$name"] ) ) ) {
			return $this->set( $name, $value );
		} else {
			return $this->set( $name, $this->encrypt_AES( $value ) );
		}
	}
}
