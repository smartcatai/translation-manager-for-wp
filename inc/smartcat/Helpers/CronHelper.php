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

use Http\Client\Common\FlexibleHttpClient;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\Socket\Client as SocketHttpClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use SmartCAT\WP\Connector;
use SmartCAT\WP\Handler\SmartcatCronHandler;
use SmartCAT\WP\WP\Options;
use SmartCAT\WP\WP\PluginInterface;

/**
 * Class CronHelper
 *
 * @package SmartCAT\WP\Helpers
 */
class CronHelper implements PluginInterface {
	/**
	 * @var string
	 */
	private $host = 'smartcat-cron-app.azurewebsites.net';
	/**
	 * @var FlexibleHttpClient
	 */
	private $http_client;
	/**
	 * @var GuzzleMessageFactory
	 */
	private $message_factory;

	/**
	 * CronHelper constructor.
	 */
	public function __construct() {
		$this->message_factory = new GuzzleMessageFactory();
		$options               = [
			'remote_socket' => "tcp://{$this->host}:443",
			'ssl'           => true,
			'ssl_method'    => STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
		];

		$socket_client = new SocketHttpClient( $this->message_factory, $options );
		$client        = new PluginClient( $socket_client, [ new ErrorPlugin(), new ContentLengthPlugin() ] );

		$this->http_client = new FlexibleHttpClient( $client );
	}

	/**
	 * @param $account
	 * @param $token
	 * @param $url
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function subscribe( $account, $token, $url ) {
		$data = array(
			'account' => $account,
			'token'   => $token,
			'url'     => $url,
		);

		$request  = $this->createRequest( "https://{$this->host}/api/subscription", $data );
		$response = $this->http_client->sendRequest( $request );

		return $response;
	}

	/**
	 * @param $account
	 * @param $url
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function unsubscribe( $account, $url ) {
		$data = array(
			'account' => $account,
			'url'     => $url,
		);

		$request  = $this->createRequest( "https://{$this->host}/api/subscription", $data, 'DELETE' );
		$response = $this->http_client->sendRequest( $request );

		return $response;
	}

	/**
	 * @param $url
	 * @param $data
	 * @param string $method
	 *
	 * @return \Psr\Http\Message\RequestInterface
	 */
	private function createRequest( $url, $data, $method = 'POST' ) {
		$headers = array_merge( array( 'Accept' => array( 'application/json' ) ) );
		$body    = json_encode( $data );

		return $this->message_factory->createRequest( $method, $url, $headers, $body );
	}

	/**
	 * @throws \Exception
	 */
	public function register() {
		/** @var Options $options */
		$options = Connector::get_container()->get( 'core.options' );

		$authorisation_token = $options->get_and_decrypt( 'cron_authorisation_token' );
		$login               = $options->get_and_decrypt( 'smartcat_api_login' );

		if ( SmartCAT::is_active() ) {
			try {
				$this->subscribe( $login, $authorisation_token, get_site_url() . '/?rest_route=/' . SmartcatCronHandler::ROUTE_PREFIX . '/cron' );
			} catch ( \Exception $e ) {
				Logger::error( "External cron", "External cron activating cause error: '{$e->getMessage()}'" );
				return;
			}
			Logger::event( "External cron", "External cron successfully activated" );
		}
	}

	/**
	 * @throws \Exception
	 */
	public function unregister() {
		/** @var Options $options */
		$options = Connector::get_container()->get( 'core.options' );
		$login   = $options->get_and_decrypt( 'smartcat_api_login' );

		if ( SmartCAT::is_active() ) {
			try {
				$this->unsubscribe( $login, get_site_url() . '/?rest_route=/' . SmartcatCronHandler::ROUTE_PREFIX  . '/cron' );
			} catch ( \Exception $e ) {
				Logger::error( "External cron", "External cron de-activating cause error: '{$e->getMessage()}'" );
				return;
			}
			Logger::event( "External cron", "External cron successfully de-activated" );
		}
	}

	/**
	 * @throws \Exception
	 */
	public function plugin_activate() {
		/** @var Options $options */
		$options = Connector::get_container()->get( 'core.options' );

		if ( ! $options->get( 'cron_authorisation_token' ) ) {
			$authorisation_token = base64_encode( openssl_random_pseudo_bytes( 32 ) );
			$options->set_and_encrypt( 'cron_authorisation_token', $authorisation_token );
		}
	}

	/**
	 * @throws \Exception
	 */
	public function plugin_deactivate() {
	}

	/**
	 *
	 */
	public function plugin_uninstall() {
	}
}
