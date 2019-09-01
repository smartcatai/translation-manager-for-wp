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

use SmartCAT\WP\DB\Repository\ProfileRepository;
use SmartCAT\WP\DITrait;
use SmartCAT\WP\Helpers\Language\LanguageConverter;
use SmartCAT\WP\Helpers\TemplateEngine;
use SmartCAT\WP\Helpers\Utils;
use SmartCAT\WP\WP\HookInterface;

/**
 * Class Frontend
 *
 * @package SmartCAT\WP\Admin
 */
final class Frontend implements HookInterface {
	use DITrait;

	/**
	 * @throws \Exception
	 */
	public static function custom_admin_footer_js() {
		wp_enqueue_style( 'sc-custom-css' );

		$container = self::get_container();
		/** @var TemplateEngine $templater */
		$templater = $container->get( 'templater' );
		/** @var ProfileRepository $profiles_repo */
		$profiles_repo = $container->get( 'entity.repository.profile' );

		$profiles = [];

		foreach ( $profiles_repo->get_all() as $profile ) {
			$profiles[] = [
				'value' => $profile->get_id(),
				'name'  => $profile->get_name(),
			];
		}

		echo $templater->render(
			'partials/sc_modal',
			[
				'profiles'      => $profiles,
				'sc_send_nonce' => wp_create_nonce( 'sc_send_nonce' ),
				'texts'         => [
					'title'           => __( 'Title', 'translation-connectors' ),
					'author'          => __( 'Author', 'translation-connectors' ),
					'status'          => __( 'Status', 'translation-connectors' ),
					'select_profile'  => __( 'Select profile', 'translation-connectors' ),
					'submit'          => __( 'Submit for translation', 'translation-connectors' ),
				],
			]
		);
	}

	/**
	 * @throws \Exception
	 */
	public static function queue_my_admin_scripts() {
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_register_script( 'smartcat-frontend', plugin_dir_url( SMARTCAT_PLUGIN_FILE ) . 'js/smartcat.js', [], Utils::get_plugin_version_file(), true );
		wp_register_style( 'sc-custom-css', plugin_dir_url( SMARTCAT_PLUGIN_FILE ) . 'css/smartcat.css', [], Utils::get_plugin_version_file() );

		$container      = self::get_container();
		$plugin_dir_url = plugin_dir_url( SMARTCAT_PLUGIN_FILE );
		/** @var LanguageConverter $languages_converter */
		$languages_converter = $container->get( 'language.converter' );
		$languages           = $languages_converter->get_polylang_languages_supported_by_sc();

		$pll_locales_array = $languages_converter->get_polylang_slugs_supported_by_sc();

		$translation_and_vars = [
			'pluginUrl'                     => $plugin_dir_url,
			'adminUrl'                      => admin_url(),
			'smartcat_table_prefix'         => $container->getParameter( 'plugin.table.prefix' ),
			'totalLanguages'                => count( $languages ),
			'pll_languages_supported_by_sc' => $pll_locales_array,

			'dialogTitle'                   => __( 'Send for translation', 'translation-connectors' ),
			'anErrorOccurred'               => __( 'An error occurred:', 'translation-connectors' ),
			'dismissNotice'                 => __( 'Dismiss this notice.', 'translation-connectors' ),
			'postsAreNotChoosen'            => __( 'Please select posts or pages for translation', 'translation-connectors' ),
			'postsAreAlreadyTranslated'     => __( 'Selected posts or pages have already been translated', 'translation-connectors' ),
		];

		wp_localize_script( 'smartcat-frontend', 'SmartcatFrontend', $translation_and_vars );
		wp_enqueue_script( 'smartcat-frontend' );

		wp_enqueue_style( 'wp-jquery-ui-dialog' );
	}

	/**
	 * @return mixed|void
	 */
	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', [ self::class, 'queue_my_admin_scripts' ] );
		add_action( 'admin_footer', [ self::class, 'custom_admin_footer_js' ] );
	}
}
