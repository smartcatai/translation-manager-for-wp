<?php

namespace SmartCAT\WP;


use SmartCAT\WP\Cron\CronInterface;
use SmartCAT\WP\DB\Repository\TaskRepository;
use SmartCAT\WP\Helpers\Language\LanguageConverter;
use SmartCAT\WP\Helpers\SmartCAT;
use SmartCAT\WP\Queue\QueueAbstract;
use SmartCAT\WP\WP\HookInterface;
use SmartCAT\WP\WP\InitInterface;
use SmartCAT\WP\WP\Notice;
use SmartCAT\WP\WP\Options;
use SmartCAT\WP\WP\PluginInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Connector {
	use DITrait;

	public static $plugin_version = null;

	public function __construct() {
		ignore_user_abort( true );
		set_time_limit( 0 );
		$this->init_cron();
		$this->register_hooks();
		/** @var TaskRepository $test */
		$test = self::get_container()->get( 'entity.repository.task' );
		$test->get_new_task();
	}

	private function init_cron() {
		$new_schedules = [];
		$services     = self::get_container()->findTaggedServiceIds( 'cron' );
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
			throw new Exception( __( 'You need to activate the plugin Polylang', 'translation-connectors' ) );
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

	public function plugin_load( /** @noinspection PhpUnusedParameterInspection */
		$query ) {
		load_plugin_textdomain( SMARTCAT_PLUGIN_NAME, false, basename(SMARTCAT_PLUGIN_DIR) . '/languages' );
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

	public function plugin_init( /** @noinspection PhpUnusedParameterInspection */
		$query ) {
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

	public function plugin_admin_notice( /** @noinspection PhpUnusedParameterInspection */
		$query ) {
		if ( ! wp_doing_ajax() ) {
			if ( ! self::check_dependency() ) {
				/** @var Notice $notice */
				$notice = $this->from_container( 'core.notice' );
				$notice->add_error( __( 'You need to activate the plugin Polylang', 'translation-connectors' ), false );
			}

			if ( ! SmartCAT::is_active() ) {
				$notice = $this->from_container( 'core.notice' );
				$notice->add_error( __( 'You must enter API login and password', 'translation-connectors' ), false );
			}
		}
	}

	public static function set_core_parameters() {
		/** @var  ContainerInterface */
		$container = self::get_container();
		/** @var Options $options */
		$options = $container->get('core.options');
		$container->setParameter( 'smartcat.api.login', $options->get_and_decrypt( 'smartcat_api_login' ) );
		$container->setParameter( 'smartcat.api.password', $options->get_and_decrypt( 'smartcat_api_password' ) );
		$container->setParameter( 'smartcat.api.server', $options->get( 'smartcat_api_server' ) );
	}

	static public function check_dependency() {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		return is_plugin_active( 'polylang/polylang.php' ) || is_plugin_active( 'polylang-pro/polylang.php' ) ;
	}

	public function post_update_hook($postId, $postBefore, $postAfter)
    {
        $defaultLanguage = pll_default_language();

        if (pll_get_post_language($postId, 'locale') != $defaultLanguage) {
        	return;
        }

        if ($postBefore->post_content == $postAfter->post_content
            && $postBefore->post_title == $postAfter->post_title) {
        	return;
        }

        $postsTranslations = pll_get_post_translations($postId);

	    $container = self::get_container();

	    /** @var LanguageConverter $languages_converter */
	    $languages_converter = $container->get( 'language.converter' );

        foreach ($postsTranslations as $slug => $postTranslationId) {
			$language = $languages_converter->get_sc_code_by_wp($slug);
			$language->get_sc_code();
        }
    }
}

