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

use SmartCAT\WP\Connector;
use SmartCAT\WP\Cron\SendToSmartCAT;
use SmartCAT\WP\Helpers\CronHelper;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\WP\HookInterface;
use SmartCAT\WP\WP\Options;
use SmartCAT\WP\WP\PluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SmartcatCronHandler implements PluginInterface, HookInterface {

	const ROUTE_PREFIX = 'smartcat-connector/cron';

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

		if ( $request->get_header( 'authorization' ) === ( 'Bearer ' . $options->get_and_decrypt( 'cron_authorisation_token' ) ) ) {
			Logger::event( 'cron', 'Starting external cron' );
			if ( ! get_transient( 'smartcat_cron_handler' ) ) {
				set_transient( 'smartcat_cron_handler', true, 59 );

				/** @var SendToSmartCAT $cron_check */
				$cron_check = $this->container->get( 'core.cron.check' );
				$cron_check->run();

				/** @var SendToSmartCAT $cron_send */
				$cron_send = $this->container->get( 'core.cron.send' );
				$cron_send->run();

				return [ 'status' => 'ok' ];
			}

			return [ 'status' => 'nok' ];
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
		if ( SmartCAT::is_active() ) {
			/** @var Options $options */
			$options = $this->container->get( 'core.options' );

			/** @var CronHelper $cron_helper */
			$cron_helper = $this->container->get( 'cron.helper' );
			$login       = $options->get_and_decrypt( 'smartcat_api_login' );
			$external    = $options->get( 'use_external_cron' );

			if ( $login && $external ) {
				$cron_helper->register();
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	public function plugin_deactivate() {
		if ( SmartCAT::is_active() ) {
			/** @var Options $options */
			$options = Connector::get_container()->get( 'core.options' );

			/** @var CronHelper $cron_helper */
			$cron_helper = $this->container->get( 'cron.helper' );
			$login       = $options->get_and_decrypt( 'smartcat_api_login' );

			try {
				if ( $login && $options->get( 'use_external_cron' ) ) {
					$cron_helper->unregister();
				}
			} catch ( \Exception $e ) {
			}

		}
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
