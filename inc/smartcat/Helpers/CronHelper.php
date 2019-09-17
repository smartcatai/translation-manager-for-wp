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
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\Socket\Client as SocketHttpClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use SmartCAT\WP\Connector;
use SmartCAT\WP\Handler\SmartcatCronHandler;
use SmartCAT\WP\WP\Options;
use SmartCAT\WP\WP\PluginInterface;

class CronHelper implements PluginInterface
{
	private $host = 'smartcat-cron-app.azurewebsites.net';
	private $httpClient;
	private $messageFactory;

	public function __construct()
	{
		$this->messageFactory = new GuzzleMessageFactory();
		$options = [
			'remote_socket' => "tcp://{$this->host}:443",
			'ssl' => true
		];

		$socketClient = new SocketHttpClient($this->messageFactory, $options);
		$client = new PluginClient($socketClient, [
			new ErrorPlugin()
		]);

		$this->httpClient = new FlexibleHttpClient($client);
	}

	/**
	 * @param $account
	 * @param $token
	 * @param $url
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function subscribe($account, $token, $url)
	{
		$data = array(
			'account' => $account,
			'token' => $token,
			'url' => $url
		);

		$request = $this->createRequest("https://{$this->host}/api/subscription", $data);
		$response = $this->httpClient->sendRequest($request);

		return $response;
	}

	/**
	 * @param $account
	 * @param $url
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function unsubscribe($account, $url)
	{
		$data = array(
			'account' => $account,
			'url' => $url
		);

		$request = $this->createRequest("https://{$this->host}/api/subscription", $data, 'DELETE');
		$response = $this->httpClient->sendRequest($request);

		return $response;
	}

	/**
	 * @param $url
	 * @param $data
	 * @param string $method
	 * @return \Psr\Http\Message\RequestInterface
	 */
	private function createRequest($url, $data, $method = 'POST')
	{
		$headers = array_merge(array('Accept' => array('application/json')));
		$body = json_encode($data);

		return $this->messageFactory->createRequest($method, $url, $headers, $body);
	}

	/**
	 * @throws \Exception
	 */
	public function register()
	{
		/** @var Options $options */
		$options = Connector::get_container()->get( 'core.options' );

		$authorisation_token = $options->get_and_decrypt( 'cron_authorisation_token' );
		$login               = $options->get_and_decrypt( 'smartcat_api_login' );

		if ( $login ) {
			$this->subscribe ($login, $authorisation_token, get_site_url() . '/wp-json/' . SmartcatCronHandler::ROUTE_PREFIX );
		}
	}

	/**
	 * @throws \Exception
	 */
	public function unregister()
	{
		/** @var Options $options */
		$options = Connector::get_container()->get( 'core.options' );
		$login   = $options->get_and_decrypt( 'smartcat_api_login' );

		if ( $login ) {
			$this->unsubscribe( $login, get_site_url() . '/wp-json/' . SmartcatCronHandler::ROUTE_PREFIX );
		}
	}

	/**
	 * @throws \Exception
	 */
	public function plugin_activate()
	{
		$authorisation_token = base64_encode( openssl_random_pseudo_bytes( 32 ) );
		/** @var Options $options */
		$options = Connector::get_container()->get( 'core.options' );
		$options->set_and_encrypt( 'cron_authorisation_token', $authorisation_token );
	}

	/**
	 * @throws \Exception
	 */
	public function plugin_deactivate()
	{
	}

	/**
	 *
	 */
	public function plugin_uninstall()
	{
	}
}
