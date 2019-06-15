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
	const ENCRYPTION = 'AES-256-CBC';

	/**
	 * Get salt from WordPress
	 *
	 * @return mixed|void
	 */
	private static function get_salt() {
		return wp_salt();
	}

	/**
	 * Get IV
	 *
	 * @return bool|string
	 */
	private static function get_iv() {
		$iv_len = openssl_cipher_iv_length( self::ENCRYPTION );
		$key    = hash( 'sha512', wp_salt( 'secure_auth' ), PASSWORD_DEFAULT );

		return substr( $key, - $iv_len );
	}

	/**
	 * Encrypt text
	 *
	 * @param string $text Text to encrypt.
	 * @return string
	 */
	public static function encrypt( $text ) {
		return openssl_encrypt( $text, self::ENCRYPTION, self::get_salt(), 0, self::get_iv() );
	}

	/**
	 * Decrypt text
	 *
	 * @param string $text Text to decrypt.
	 * @return string
	 */
	public static function decrypt( $text ) {
		return openssl_decrypt( $text, self::ENCRYPTION, self::get_salt(), 0, self::get_iv() );
	}
}
