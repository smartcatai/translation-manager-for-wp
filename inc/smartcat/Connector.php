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

namespace SmartCAT\WP;

use SmartCAT\WP\Cron\CronInterface;
use SmartCAT\WP\DB\Setup\SetupInterface;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\Helpers\Utils;
use SmartCAT\WP\Queue\QueueAbstract;
use SmartCAT\WP\WP\HookInterface;
use SmartCAT\WP\WP\InitInterface;
use SmartCAT\WP\WP\Notice;
use SmartCAT\WP\WP\Options;
use SmartCAT\WP\WP\PluginInterface;

/**
 * Class Connector
 *
 * @package SmartCAT\WP
 */
class Connector {
	use DITrait;

	public static $plugin_version = null;

	public function __construct() {
		ignore_user_abort( true );
		set_time_limit( 0 );

		if ( self::check_dependency() ) {
			$this->init_cron();
			$this->register_hooks();
		}
	}

	private function init_cron() {
		$new_schedules = [];
		$services	 = self::get_container()->findTaggedServiceIds( 'cron' );
		foreach ( $services as $service => $tags ) {
			$object = $this->from_container( $service );
			if ( $object instanceof CronInterface ) {
				$new_schedules = array_merge( $new_schedules, $object->get_interval() );
			}
		}

		add_filter( 'cron_schedules', function ( $schedules ) use ( $new_schedules ) {
			$schedules = array_merge( $schedules, $new_schedules );

			return $schedules;
		} );
	}

	private function register_hooks() {
		$hooks = self::get_container()->findTaggedServiceIds( 'hook' );
		foreach ( $hooks as $hook => $tags ) {
			$object = $this->from_container( $hook );
			if ( $object instanceof HookInterface ) {
				$object->register_hooks();
			}
		}
	}

	private function init_queue() {
		$services = self::get_container()->findTaggedServiceIds( 'queue' );
		foreach ( $services as $service => $tags ) {
			$this->from_container( $service );
		}
	}

	public function plugin_activate() {
		if ( ! self::check_dependency() ) {
			/** @var Notice $notice */
			throw new \Exception( __( 'You need to activate the plugin Polylang', 'translation-connectors' ) );
		}
		self::set_core_parameters();

		$hooks = self::get_container()->findTaggedServiceIds( 'installable' );
		foreach ( $hooks as $hook => $tags ) {
			$object = $this->from_container( $hook );
			if ( $object instanceof PluginInterface ) {
				$object->plugin_activate();
			}
		}

		flush_rewrite_rules();
	}

	public function plugin_update( $upgrader_object, $options ) {
		$plugin_file = plugin_basename( SMARTCAT_PLUGIN_FILE );

		if ( 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
			if ( in_array( $plugin_file, $options['plugins'], true ) ) {
				$param_prefix = self::get_container()->getParameter( 'plugin.table.prefix' );
				$hooks = self::get_container()->findTaggedServiceIds( 'update' );
				foreach ( $hooks as $hook => $tags ) {
					$object = $this->from_container( $hook );
					if ( $object instanceof SetupInterface ) {
						$object->install();
					}
				}
				update_option( $param_prefix . 'smartcat_db_version', Utils::get_plugin_version_file() );
			}
		}
	}

	public function plugin_deactivate() {
		//Деактивация компонентов плагина
		$hooks = self::get_container()->findTaggedServiceIds( 'installable' );
		foreach ( $hooks as $hook => $tags ) {
			$object = $this->from_container( $hook );
			if ( $object instanceof PluginInterface ) {
				$object->plugin_deactivate();
			}
		}
		//Остановка очередей
		$hooks = self::get_container()->findTaggedServiceIds( 'queue' );
		foreach ( $hooks as $hook => $tags ) {
			$object = $this->from_container( $hook );
			if ( $object instanceof QueueAbstract ) {
				$object->cancel_process();
			}
		}
	}

	public function plugin_load( /** @noinspection PhpUnusedParameterInspection */ $query ) {
		load_plugin_textdomain( SMARTCAT_PLUGIN_NAME, false, basename( SMARTCAT_PLUGIN_DIR ) . '/languages' );
	}

	static public function plugin_uninstall() {
		$hooks = self::get_container()->findTaggedServiceIds( 'installable' );
		foreach ( $hooks as $hook => $tags ) {
			$object = self::get_container()->get( $hook );
			if ( $object instanceof PluginInterface ) {
				$object->plugin_uninstall();
			}
		}
	}

	public function plugin_init( /** @noinspection PhpUnusedParameterInspection */ $query ) {
		$this->init_queue();
		$hooks = self::get_container()->findTaggedServiceIds( 'initable' );
		foreach ( $hooks as $hook => $tags ) {
			$object = self::get_container()->get( $hook );
			if ( $object instanceof InitInterface ) {
				$object->plugin_init();
			}
		}
		self::set_core_parameters();
	}

	public function plugin_admin_notice( /** @noinspection PhpUnusedParameterInspection */ $query ) {
		if ( ! wp_doing_ajax() ) {
			/** @var Notice $notice */
			$notice = $this->from_container( 'core.notice' );
			if ( ! self::check_dependency() ) {
				$notice->add_error( __( 'You need to activate the plugin Polylang', 'translation-connectors' ), false );
			}

			if ( ! SmartCAT::is_active() ) {
				$notice->add_error( __( 'You must enter API login and password', 'translation-connectors' ), false );
			} else {
				if ( ! SmartCAT::check_access() ) {
					$notice->add_error( __( 'Smartcat credentials are incorrect. Login failed.', 'translation-connectors' ), false );
				}
			}
		}
	}

	public static function set_core_parameters() {
		/** @var  ContainerInterface */
		$container = self::get_container();
		/** @var Options $options */
		$options = $container->get( 'core.options' );
		$container->setParameter( 'smartcat.api.login', $options->get_and_decrypt( 'smartcat_api_login' ) );
		$container->setParameter( 'smartcat.api.password', $options->get_and_decrypt( 'smartcat_api_password' ) );
		$container->setParameter( 'smartcat.api.server', $options->get( 'smartcat_api_server' ) );
	}

	public static function check_dependency() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		return is_plugin_active( 'polylang/polylang.php' ) || is_plugin_active( 'polylang-pro/polylang.php' );
	}
}
