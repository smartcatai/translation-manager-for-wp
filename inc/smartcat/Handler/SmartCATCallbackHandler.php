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
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\WP\HookInterface;
use SmartCAT\WP\WP\Options;
use SmartCAT\WP\WP\PluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Обработка запросов от callback smartCAT
 * Class SmartCATCallbackHandler
 *
 * @package SmartCAT\WP\Handler
 */
class SmartCATCallbackHandler implements PluginInterface, HookInterface {

	const ROUTE_PREFIX = 'smartcat-connector/callback';

	/** @var  ContainerInterface */
	private $container;

	/**
	 * SmartCATCallbackHandler constructor.
	 */
	public function __construct() {
		$this->container = Connector::get_container();
	}

	/**
	 * @param \WP_REST_Server $server
	 */
	public function register_rest_route( \WP_REST_Server $server ) {
		register_rest_route(
			self::ROUTE_PREFIX,
			'/(?<type>.+)/(?<method>.+)',
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'handle' ],
			]
		);
	}

	/**
	 * Обрабатываем запрос пришедшие от smartCAT
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array|\WP_Error
	 */
	public function handle( \WP_REST_Request $request ) {
		/** @var Options $options */
		$options = $this->container->get( 'core.options' );

		if ( $request->get_header( 'authorization' ) === $options->get_and_decrypt( 'callback_authorisation_token' ) ) {
			return ['spawn_cron' => spawn_cron()];
		} else {
			$response = new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to do that.', 'translation-connectors' ),
				[ 'status' => 403 ]
			);

			return $response;
		}
	}

	/**
	 * @throws \Exception
	 */
	public function plugin_activate() {
		$authorisation_token = 'Bearer ' . base64_encode( openssl_random_pseudo_bytes( 32 ) );
		/** @var Options $options */
		$options = $this->container->get( 'core.options' );
		$options->set_and_encrypt( 'callback_authorisation_token', $authorisation_token );
		$this->register_callback();
	}

	/**
	 * @param SmartCAT $smartcat
	 *
	 * @throws \Exception
	 */
	public function register_callback( $smartcat = null ) {
		if ( SmartCAT::is_active() ) {
			/** @var Options $options */
			$options = $this->container->get( 'core.options' );

			if ( ! $smartcat ) {
				$smartcat = $this->container->get( 'smartcat' );
			}

			$callback_model = new CallbackPropertyModel();
			$callback_model->setUrl( get_site_url() . '/wp-json/' . self::ROUTE_PREFIX );
			$callback_model->setAdditionalHeaders(
				[
					[
						'name'  => 'Authorization',
						'value' => $options->get_and_decrypt( 'callback_authorisation_token' ),
					],
				]
			);
			$smartcat->getCallbackManager()->callbackUpdate( $callback_model );
		}
	}

	/**
	 * @throws \Exception
	 */
	public function delete_callback() {
		if ( SmartCAT::is_active() ) {
			/** @var SmartCAT $sc */
			$sc = $this->container->get( 'smartcat' );
			$sc->getCallbackManager()->callbackDelete();
		}
	}

	/**
	 * @throws \Exception
	 */
	public function plugin_deactivate() {
		$this->delete_callback();
	}

	/**
	 *
	 */
	public function plugin_uninstall() {
	}

	/**
	 * @return mixed|void
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', [ $this, 'register_rest_route' ] );
	}
}
