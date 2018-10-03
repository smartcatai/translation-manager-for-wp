<?php

namespace SmartCAT\WP\Helpers;

class Cryptographer {
	private static function get_salt() {
		return \wp_salt();
	}

	private static function get_IV() {
		$iv_len = openssl_cipher_iv_length( 'AES-256-CBC' );
		$key    = hash( 'sha512', wp_salt( 'secure_auth' ), PASSWORD_DEFAULT );

		return substr( $key, - $iv_len );
	}

	public static function encrypt( $text ) {
		return openssl_encrypt( $text, 'AES-256-CBC', self::get_salt(), 0, self::get_IV() );
	}

	public static function decrypt( $text ) {
		return openssl_decrypt( $text, 'AES-256-CBC', self::get_salt(), 0, self::get_IV() );
	}
}