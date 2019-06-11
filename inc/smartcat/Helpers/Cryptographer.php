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

class Cryptographer
{
	private static function getSalt()
	{
		return wp_salt();
	}

	private static function getIv()
	{
		$ivLen = openssl_cipher_iv_length( 'AES-256-CBC' );
		$key	= hash( 'sha512', wp_salt( 'secure_auth' ), PASSWORD_DEFAULT );

		return substr( $key, - $ivLen );
	}

	public static function encrypt( $text )
	{
		return openssl_encrypt( $text, 'AES-256-CBC', self::getSalt(), 0, self::getIv() );
	}

	public static function decrypt( $text )
	{
		return openssl_decrypt( $text, 'AES-256-CBC', self::getSalt(), 0, self::getIv() );
	}
}
