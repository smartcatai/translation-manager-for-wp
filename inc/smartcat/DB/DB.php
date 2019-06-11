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

namespace SmartCAT\WP\DB;

use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Setup\SetupInterface;
use SmartCAT\WP\WP\PluginInterface;

class DB implements PluginInterface
{
	public function plugin_activate()
	{
		$repositories = Connector::get_container()->findTaggedServiceIds( 'setup' );
		foreach ( $repositories as $repository => $tag ) {
			$object = Connector::get_container()->get( $repository );
			if ( $object instanceof SetupInterface ) {
				$object->install();
			}
		}
		update_option( 'smartcat_connector_smartcat_db_version', $this->get_file_version() );
	}

	public function plugin_deactivate()
	{
	}

	public function plugin_uninstall()
	{
		$repositories = Connector::get_container()->findTaggedServiceIds( 'setup' );
		foreach ( $repositories as $repository => $tag ) {
			$object = Connector::get_container()->get( $repository );
			if ( $object instanceof SetupInterface ) {
				$object->uninstall();
			}
		}
		delete_option( 'smartcat_connector_smartcat_db_version' );
	}

	private function get_file_version()
	{
		if ( defined( 'SMARTCAT_PLUGIN_FILE' ) ) {
			$plugin_data = get_file_data( SMARTCAT_PLUGIN_FILE, ['Version' => 'Version'] );
			return trim( $plugin_data['Version'] );
		}
		return 0;
	}
}
