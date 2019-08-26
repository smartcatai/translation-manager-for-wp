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

namespace SmartCAT\WP\Handler;

use SmartCat\Client\Model\CallbackPropertyModel;
use SmartCAT\WP\Connector;
use SmartCAT\WP\Cron\CheckProjectsStatus;
use SmartCAT\WP\Cron\SendToSmartCAT;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\WP\HookInterface;
use SmartCAT\WP\WP\Options;
use SmartCAT\WP\WP\PluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Обработка запросов от callback smartCAT
 * Class SmartCATCallbackHandler
 * @package SmartCAT\WP\Handler
 */
class SmartCATCallbackHandler implements PluginInterface, HookInterface {

	const ROUTE_PREFIX = 'smartcat-connector/callback';

	/** @var  ContainerInterface */
	private $container;

	public function __construct() {
		$this->container = Connector::get_container();
	}

	public function register_rest_route(
		/** @noinspection PhpUnusedParameterInspection */
		\WP_REST_Server $server
	) {
		register_rest_route( self::ROUTE_PREFIX, '/(?<type>.+)/(?<method>.+)', [
			'methods'  => \WP_REST_Server::CREATABLE,
			'callback' => [ $this, 'handle' ],
		] );

	}

	/**
	 * Обрабатываем запрос пришедшие от smartCAT
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array|\WP_Error
	 */
	public function handle( \WP_REST_Request $request ) {
		if ( $request->get_param( 'type' ) == 'document' && $request->get_param( 'method' ) == 'status' ) {
			/** @var Options $options */
			$options = $this->container->get( 'core.options' );

			if ( $request->get_header( 'authorization' ) == $options->get_and_decrypt( 'callback_authorisation_token' ) ) {
				if ( time() - $options->get( 'last_cron_check' ) > 300 ) {
					/** @var CheckProjectsStatus $cron_checker */
					$cron_checker = $this->container->get( 'core.cron.check' );
					$cron_checker->run();
				}

				if ( time() - $options->get( 'last_cron_send' ) > 300 ) {
					/** @var SendToSmartCAT $cron_checker */
					$cron_checker = $this->container->get( 'core.cron.send' );
					$cron_checker->run();
				}

				$response = new \WP_Error(
					'rest_forbidden',
					__( 'Sorry, you can not use cron so fast.', 'translation-connectors' ),
					[ 'status' => 400 ]
				);

				return $response;
			} else {
				$response = new \WP_Error( 'rest_forbidden',
					__( 'Sorry, you are not allowed to do that.', 'translation-connectors' ),
					[ 'status' => 403 ] );

				return $response;
			}
		}

		return [ 'message' => 'ok' ];
	}

	public function plugin_activate() {
		$authorisation_token = "Bearer " . base64_encode( openssl_random_pseudo_bytes( 32 ) );
		/** @var Options $options */
		$options = $this->container->get( 'core.options' );
		$options->set_and_encrypt( 'callback_authorisation_token', $authorisation_token );
		$this->register_callback();
	}

	public function register_callback() {
		if ( SmartCAT::is_active() ) {
			/** @var Options $options */
			$options = $this->container->get( 'core.options' );

			/** @var SmartCAT $sc */
			$sc			 = $this->container->get( 'smartcat' );
			$callback_model = new CallbackPropertyModel();
			$callback_model->setUrl( get_site_url() . '/wp-json/' . self::ROUTE_PREFIX );
			$callback_model->setAdditionalHeaders( [
				[
					'name'  => 'Authorization',
					'value' => $options->get_and_decrypt( 'callback_authorisation_token' ),
				]
			] );
			$sc->getCallbackManager()->callbackUpdate( $callback_model );
		}
	}

	public function plugin_deactivate() {
		if ( SmartCAT::is_active() ) {
			/** @var SmartCAT $sc */
			$sc = $this->container->get( 'smartcat' );
			$sc->getCallbackManager()->callbackDelete();
		}
	}

	public function plugin_uninstall() {
	}

	public function register_hooks() {
		add_action( 'rest_api_init', [ $this, 'register_rest_route' ] );
	}
}
