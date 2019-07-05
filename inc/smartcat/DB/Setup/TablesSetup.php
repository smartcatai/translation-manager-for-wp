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

namespace SmartCAT\WP\DB\Setup;

use SmartCAT\WP\DB\DbAbstract;

/**
 * Class TablesSetup
 *
 * @package SmartCAT\WP\DB\Setup
 */
class TablesSetup extends DbAbstract implements SetupInterface {
	private $prefix = '';

	/**
	 * TablesSetup constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->prefix = $this->get_wp_db()->get_blog_prefix();
	}

	/**
	 * Install plugin function
	 */
	public function install() {
		$this->initial();
	}

	/**
	 * Uninstall plugin function
	 */
	public function uninstall() {
		$this->drop_table( $this->prefix . 'smartcat_connector_tasks' );
		$this->drop_table( $this->prefix . 'smartcat_connector_statistic' );
		$this->drop_table( $this->prefix . 'smartcat_connector_errors' );
	}

	/**
	 * Initial database function
	 */
	private function initial() {
		$tasks_table_name = $this->prefix . 'smartcat_connector_tasks';
		$sql              = "CREATE TABLE IF NOT EXISTS {$tasks_table_name} ( 
				id  BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT,
				sourceLanguage VARCHAR( 255 ) NOT NULL,
				targetLanguages TEXT NOT NULL,
				postID BIGINT( 20 ) UNSIGNED NOT NULL,
				status VARCHAR( 20 ) NOT NULL DEFAULT 'new',
				projectID VARCHAR( 255 ),
				PRIMARY KEY  ( id ),
				INDEX status ( `status` )
			 );";

		$this->create_table( $sql );

		$statistic_table_name = $this->prefix . 'smartcat_connector_statistic';
		$sql                  = "CREATE TABLE IF NOT EXISTS {$statistic_table_name} ( 
				id  BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT,
				taskId BIGINT( 20 ) UNSIGNED NOT NULL,
				postID BIGINT( 20 ) UNSIGNED NOT NULL,
				sourceLanguage VARCHAR( 255 ) NOT NULL,
				targetLanguage VARCHAR( 255 ) NOT NULL,
				progress DECIMAL( 10,2 ) NOT NULL DEFAULT '0',
				wordsCount BIGINT( 20 ) UNSIGNED,
				targetPostID BIGINT( 20 ) UNSIGNED,
				documentID VARCHAR( 255 ),
				status VARCHAR( 20 ) NOT NULL DEFAULT 'new',
				errorCount BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY  ( id ),
				INDEX status ( `status` ),
				INDEX documentID ( `documentID` )
			 ) ROW_FORMAT=DYNAMIC;";

		$this->create_table( $sql );

		$errors_table_name = $this->prefix . 'smartcat_connector_errors';
		$sql               = "CREATE TABLE IF NOT EXISTS {$errors_table_name} ( 
				`id` BIGINT NOT NULL AUTO_INCREMENT, 
				`date` DATETIME NOT NULL, 
				`type` VARCHAR( 255 ) NOT NULL,
				`shortMessage` VARCHAR( 255 ) NOT NULL,
				`message` TEXT NOT NULL,
				PRIMARY KEY ( `id` ),
				INDEX ( `date` )
			 );";

		$this->create_table( $sql );
	}
}
