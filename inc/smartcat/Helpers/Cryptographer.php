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

/**
 * Class Cryptographer
 *
 * @package SmartCAT\WP\Helpers
 */
class Cryptographer {
	/**
	 * @return mixed|void
	 */
	private static function get_salt() {
		return wp_salt();
	}

	/**
	 * @return bool|string
	 */
	private static function get_iv() {
		$iv_len = openssl_cipher_iv_length( 'AES-256-CBC' );
		$key    = hash( 'sha512', wp_salt( 'secure_auth' ), PASSWORD_DEFAULT );

		return substr( $key, - $iv_len );
	}

	/**
	 * @param $text
	 *
	 * @return string
	 */
	public static function encrypt( $text ) {
		return openssl_encrypt( $text, 'AES-256-CBC', self::get_salt(), 0, self::get_iv() );
	}

	/**
	 * @param $text
	 *
	 * @return string
	 */
	public static function decrypt( $text ) {
		return openssl_decrypt( $text, 'AES-256-CBC', self::get_salt(), 0, self::get_iv() );
	}
}
