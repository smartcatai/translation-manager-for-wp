<?php

namespace SmartCAT\WP\Helpers;

class TemplateEngine extends \Mustache_Engine {
	public function __construct( array $options = array() ) {
		$options = array_merge($options, [
			'loader' => new \Mustache_Loader_FilesystemLoader(SMARTCAT_PLUGIN_DIR . 'views', ['extension' => '.html']),
			'partials_loader' => new \Mustache_Loader_FilesystemLoader(SMARTCAT_PLUGIN_DIR . 'views/partials'),
		]);
		parent::__construct( $options );
	}

	public function ob_to_string($function, ...$args)
	{
		try {
			ob_start();
			call_user_func_array($function, $args);
			return ob_get_clean();
		} catch (\Throwable $e) {
			Logger::warning("Can't call user func '{$function}' to string. {$e->getMessage()}");
		}

		return false;
	}
}