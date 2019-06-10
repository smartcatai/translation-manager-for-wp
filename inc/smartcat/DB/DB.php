<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\DB;

use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Repository\RepositoryInterface;
use SmartCAT\WP\WP\PluginInterface;

class DB implements PluginInterface {

	public function plugin_activate() {
		$repositories = Connector::get_container()->findTaggedServiceIds( 'repositories' );
		foreach ( $repositories as $repository => $tag ) {
			$object = Connector::get_container()->get( $repository );
			if ( $object instanceof RepositoryInterface ) {
				$object->install();
			}
		}
		update_option('smartcat_connector_smartcat_db_version', '1.2.2');
	}

    public function plugin_deactivate()
    {
    }

	public function plugin_uninstall() {
		$repositories = Connector::get_container()->findTaggedServiceIds( 'repositories' );
		foreach ( $repositories as $repository => $tag ) {
			$object = Connector::get_container()->get( $repository );
			if ( $object instanceof RepositoryInterface ) {
				$object->uninstall();
			}
		}
		delete_option('smartcat_connector_smartcat_db_version');
	}
}
