<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 16.06.2017
 * Time: 12:37
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
		update_option('smartcat_db_version', '1.2.1');
	}

	public function plugin_deactivate() {

	}

	public function plugin_uninstall() {
		$repositories = Connector::get_container()->findTaggedServiceIds( 'repositories' );
		foreach ( $repositories as $repository => $tag ) {
			$object = Connector::get_container()->get( $repository );
			if ( $object instanceof RepositoryInterface ) {
				$object->uninstall();
			}
		}
		delete_option('smartcat_db_version');
	}
}