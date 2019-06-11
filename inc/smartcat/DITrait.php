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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

trait DITrait
{
	/**
	 * @var ContainerBuilder $container
	 */
	private static $container_instance = null;

	/**
	 * Initializes DI Container from YAML config file
	 */
	protected static function init_container()
	{
		$container = new ContainerBuilder();

		$config_dir = [ SMARTCAT_PLUGIN_DIR . 'inc' . DIRECTORY_SEPARATOR . 'configs' ];

		$file_locator = new FileLocator( $config_dir );

		$loader = new YamlFileLoader( $container, $file_locator );

		$loader->load( 'autoload.yml' );
		$config_files = $container->getParameter( 'config.files' );
		foreach ( $config_files as $configFile ) {
			$loader->load( $configFile );
		}

		self::$container_instance = $container;
	}

	/**
	 * Extracts mixed from container
	 *
	 * @param string $id
	 * @param bool $isParam
	 *
	 * @return mixed
	 */
	protected function from_container( $id, $isParam = false )
	{
		$container = self::get_container();
		$content   = null;

		if ( $isParam ) {
			$content = $container->getParameter( $id );
		} else {
			$content = $container->get( $id );
		}

		return $content;
	}

	/**
	 * @return ContainerBuilder
	 */
	public static function get_container()
	{
		if ( null === self::$container_instance ) {
			self::init_container();
		}

		return self::$container_instance;
	}
}
