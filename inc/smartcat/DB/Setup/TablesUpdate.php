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

use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\DbAbstract;
use SmartCAT\WP\DB\Entity\Profile;
use SmartCAT\WP\DB\Repository\ProfileRepository;
use SmartCAT\WP\Helpers\Language\LanguageConverter;

/**
 * Class TablesUpdate
 *
 * @package SmartCAT\WP\DB\Setup
 */
class TablesUpdate extends DbAbstract implements SetupInterface {
	/**
	 * Database prefix
	 *
	 * @var string
	 */
	private $prefix = '';

	/**
	 * TablesSetup constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->prefix = $this->get_wp_db()->get_blog_prefix();
	}

	/**
	 * Main update function
	 */
	public function install() {
		if ( version_compare( $this->get_plugin_version(), '2.0.0', '<' ) ) {
			$this->v200();
		}
	}

	/**
	 * Update to version 2.0.0
	 */
	private function v200() {
		$container    = Connector::get_container();
		$param_prefix = $container->getParameter( 'plugin.table.prefix' );

		$tasks_table_name = $this->prefix . 'smartcat_connector_tasks';
		$this->exec( "ALTER TABLE {$tasks_table_name} ADD COLUMN profileID BIGINT( 20 ) UNSIGNED NOT NULL;" );
		$this->exec( "ALTER TABLE {$tasks_table_name} DROP COLUMN postID;" );
		$this->exec( "ALTER TABLE {$tasks_table_name} DROP COLUMN status;" );

		if ( get_option( $param_prefix . 'smartcat_workflow_stages', false ) ) {
			/** @var ProfileRepository $profile_repo */
			$profile_repo = $container->get( 'entity.repository.profile' );
			/** @var LanguageConverter $language_converter */
			$language_converter = $container->get( 'language.converter' );

			$default_language = pll_default_language( 'locale' );

			$target_languages = array_filter(
				$language_converter->get_polylang_locales_supported_by_sc(),
				function ( $locale ) use ( $default_language ) {
					return $default_language !== $locale;
				}
			);

			$profile = new Profile();
			$profile
				->set_name( 'Migrated from v1.*.* settings' )
				->set_source_language( $default_language )
				->set_target_languages( $target_languages )
				->set_auto_send( false )
				->set_auto_update( false )
				->set_workflow_stages( get_option( $param_prefix . 'smartcat_workflow_stages' ) )
				->set_vendor( get_option( $param_prefix . 'smartcat_vendor_id' ) )
				->set_vendor_name( get_option( $param_prefix . 'smartcat_account_name' ) );

			$project_id = get_option( $param_prefix . 'smartcat_api_project_id' );

			if ( $project_id ) {
				$profile->set_project_id( $project_id );
			}

			$profile_repo->add( $profile );
			$this->exec( "UPDATE {$tasks_table_name} SET profileID = 1 WHERE profileID IS NULL;" );
			delete_option( $param_prefix . 'smartcat_workflow_stages' );
			delete_option( $param_prefix . 'smartcat_vendor_id' );
			delete_option( $param_prefix . 'smartcat_account_name' );
			delete_option( $param_prefix . 'smartcat_api_project_id' );
		}
	}

	/**
	 * Main rollback function
	 */
	public function uninstall() {
	}
}
