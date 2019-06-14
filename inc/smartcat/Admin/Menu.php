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

namespace SmartCAT\WP\Admin;

use SmartCAT\WP\Admin\Pages\Dashboard;
use SmartCAT\WP\Admin\Pages\Errors;
use SmartCAT\WP\Admin\Pages\ProfileEdit;
use SmartCAT\WP\Admin\Pages\Profiles;
use SmartCAT\WP\Admin\Pages\Settings;
use SmartCAT\WP\Connector;
use SmartCAT\WP\WP\InitInterface;
use SmartCAT\WP\WP\Options;

/**
 * Class Settings
 *
 * @package SmartCAT\WP\Admin
 */
final class Menu implements InitInterface {
	/**
	 * Render menu in sidebar
	 */
	public static function add_admin_menu() {
		$container = Connector::get_container();
		/** @var Options $options */
		$options = $container->get( 'core.options' );

		add_menu_page(
			__( 'Localization', 'translation-connectors' ),
			__( 'Localization', 'translation-connectors' ),
			'edit_pages',
			'sc-dashboard',
			[ Dashboard::class, 'render' ],
			'dashicons-translation'
		);

		$dashboard_hook = add_submenu_page(
			'sc-dashboard',
			__( 'Dashboard', 'translation-connectors' ),
			__( 'Dashboard', 'translation-connectors' ),
			'edit_pages',
			'sc-dashboard',
			[ Dashboard::class, 'render' ]
		);

		add_action(
			"load-$dashboard_hook",
			function () {
				add_screen_option(
					'per_page',
					[
						'label'   => __( 'Show per page', 'translation-connectors' ),
						'default' => 20,
						'option'  => 'sc_dashboard_per_page',
					]
				);
			}
		);

		$profiles_hook = add_submenu_page(
			'sc-dashboard',
			__( 'Profiles', 'translation-connectors' ),
			__( 'Profiles', 'translation-connectors' ),
			'edit_pages',
			'sc-profiles',
			[ Profiles::class, 'render' ]
		);

		add_submenu_page(
			'sc-profiles',
			__( 'New profile', 'translation-connectors' ),
			__( 'New profile', 'translation-connectors' ),
			'edit_pages',
			'sc-edit-profile',
			[ ProfileEdit::class, 'render' ]
		);

		add_action(
			"load-$profiles_hook",
			function () {
				add_screen_option(
					'per_page',
					[
						'label'   => __( 'Show per page', 'translation-connectors' ),
						'default' => 20,
						'option'  => 'sc_profiles_per_page',
					]
				);
			}
		);

		if ( $options->get( 'smartcat_debug_mode' ) ) {
			add_submenu_page(
				'sc-dashboard',
				__( 'Errors', 'translation-connectors' ),
				__( 'Errors', 'translation-connectors' ),
				'edit_pages',
				'sc-errors',
				[ Errors::class, 'render' ]
			);
		}

		add_submenu_page(
			'sc-dashboard',
			__( 'Settings', 'translation-connectors' ),
			__( 'Settings', 'translation-connectors' ),
			'edit_pages',
			'sc-settings',
			[ Settings::class, 'render' ]
		);
	}

	/**
	 * Init plugin
	 *
	 * @return mixed|void
	 */
	public function plugin_init() {
		Settings::apply_filters_to_settings();
	}
}
