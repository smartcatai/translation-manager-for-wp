<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 20.06.2017
 * Time: 20:13
 */

namespace SmartCAT\WP\Helpers;


use SmartCAT\WP\Connector;

class SmartCAT extends \SmartCAT\API\SmartCAT {
	/**
	 * Проверяет можно ли использовать АПИ. Имеются ли сохраненые в настройках данные для доступа к АПИ
	 */
	public static function is_active() {
		$container = Connector::get_container();
		$login     = $container->getParameter( 'smartcat.api.login' );
		$password  = $container->getParameter( 'smartcat.api.password' );
		$server    = $container->getParameter( 'smartcat.api.server' );

		return $login && $password && $server;
	}

	public static function filter_chars( $s ) {
		return str_replace( [ '*', '|', '\\', ':', '"', '<', '>', '?', '/' ], '_', $s );
	}
}