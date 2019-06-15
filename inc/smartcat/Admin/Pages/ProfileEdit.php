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

namespace SmartCAT\WP\Admin\Pages;

use SmartCAT\WP\DB\Entity\Profile;
use SmartCAT\WP\DB\Repository\ProfileRepository;
use SmartCAT\WP\Helpers\Language\LanguageConverter;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;

/**
 * Class ProfileEdit
 *
 * @package SmartCAT\WP\Admin\Pages
 */
class ProfileEdit extends PageAbstract {
	/**
	 * Render profiles page
	 */
	public static function render() {
		$profile    = [];
		$profile_db = null;

		$sanitized_id = intval( sanitize_key( $_GET['profile'] ?? null ) );

		if ( $sanitized_id ) {
			$profile_db = self::get_repository()->get_one_by_id( $sanitized_id );

			if ( $profile_db ) {
				$profile = [
					'id'              => $profile_db->get_id(),
					'project_id'      => $profile_db->get_project_id(),
					'name'            => $profile_db->get_name(),
					'vendor'          => $profile_db->get_vendor(),
					'source_lang'     => $profile_db->get_source_language(),
					'target_langs'    => $profile_db->get_target_languages(),
					'workflow_stages' => $profile_db->get_workflow_stages(),
					'auto_send'       => $profile_db->is_auto_send(),
					'auto_update'     => $profile_db->is_auto_update(),
				];
			}
		}

		echo self::get_renderer()->render(
			'profile_edit',
			[
				'texts'           => self::get_texts( $profile_db ),
				'profile'         => $profile,
				'sc_nonce'        => wp_create_nonce( 'sc_profile_edit' ),
				'workflow_stages' => self::get_workflow_stages( $profile_db ),
				'source_lang'     => self::get_languages( $profile_db, 'source_language' ),
				'target_langs'    => self::get_languages( $profile_db, 'target_languages' ),
				'vendors'         => self::get_vendors( $profile_db ),
			]
		);
	}

	/**
	 * Get texts array for render
	 *
	 * @param Profile $profile Set a profile if is editing page.
	 * @return array
	 */
	private static function get_texts( $profile = null ) {
		if ( ! $profile ) {
			$title = __( 'New Profile', 'translation-connectors' );
		} else {
			$title = __( 'Edit Profile', 'translation-connectors' ) . " '{$profile->get_name()}'";
		}

		return [
			'empty'                   => __( 'Profiles not found', 'translation-connectors' ),
			'pages'                   => __( 'Pages', 'translation-connectors' ),
			'title'                   => $title,
			'save_button'             => ! $profile ? __( 'Create profile', 'translation-connectors' ) : __( 'Save profile', 'translation-connectors' ),
			'profile_name'            => __( 'Name', 'translation-connectors' ),
			'profile_vendor'          => __( 'Vendor', 'translation-connectors' ),
			'profile_source_lang'     => __( 'Source Language', 'translation-connectors' ),
			'profile_target_langs'    => __( 'Target Languages', 'translation-connectors' ),
			'profile_workflow_stages' => __( 'Workflow Stages', 'translation-connectors' ),
			'profile_project_id'      => __( 'Project id', 'translation-connectors' ),
			'profile_project_id_note' => __( 'Enter here a Smartcat project ID to send all ongiong tasks to one specific project.', 'translation-connectors' ),
			'profile_name_note'       => __( 'Leave this field blank for automatic name generation', 'translation-connectors' ),
			'profile_auto_update'     => __( 'Auto update edited post', 'translation-connectors' ),
			'profile_auto_send'       => __( 'Auto send created post', 'translation-connectors' ),
		];
	}

	/**
	 * Get workflow stages array
	 *
	 * @param Profile $profile Set a profile if is editing page.
	 * @return array
	 */
	private static function get_workflow_stages( $profile = null ) {
		$workflow_stages = $profile ? $profile->get_workflow_stages() : [];

		return [
			[
				'value'    => 'Translation',
				'name'     => __( 'Translation', 'translation-connectors' ),
				'selected' => $profile ? in_array( 'Translation', $workflow_stages, true ) : true,
			],
			[
				'value'    => 'Editing',
				'name'     => __( 'Editing', 'translation-connectors' ),
				'selected' => in_array( 'Editing', $workflow_stages, true ),
			],
			[
				'value'    => 'Proofreading',
				'name'     => __( 'Proofreading', 'translation-connectors' ),
				'selected' => in_array( 'Proofreading', $workflow_stages, true ),
			],
		];
	}

	/**
	 * Get languages array
	 *
	 * @param Profile $profile Set a profile if is editing page.
	 * @param string  $key Key for getter.
	 * @return array
	 */
	private static function get_languages( $profile, $key ) {
		$languages      = [];
		$search_array   = [];
		$polylang_langs = self::get_language_converter()->get_polylang_languages_supported_by_sc();

		if ( $profile ) {
			$search_array = is_array( $profile->{"get_{$key}"}() ) ? $profile->{"get_{$key}"}() : [ $profile->{"get_{$key}"}() ];
		}

		foreach ( $polylang_langs as $locale => $name ) {
			$languages[] = [
				'value'    => $locale,
				'name'     => $name,
				'selected' => in_array( $locale, $search_array, true ),
			];
		}

		return $languages;
	}

	/**
	 * Get vendors array
	 *
	 * @param Profile $profile Set a profile if is editing page.
	 * @return array
	 */
	private static function get_vendors( $profile = null ) {
		$vendors = [
			[
				'value'    => '',
				'name'     => __( 'Translate internally', 'translation-connectors' ),
				'selected' => true,
			],
		];

		try {
			$vendor_list = wp_cache_get( 'vendor_list', 'translation-connectors' );

			if ( ! $vendor_list ) {
				$vendor_list = self::get_smartcat()->getDirectoriesManager()->directoriesGet( [ 'type' => 'vendor' ] )->getItems();
				wp_cache_set( 'vendor_list', $vendor_list, 'translation-connectors', 3600 );
			}

			foreach ( $vendor_list as $vendor ) {
				$vendors = array_merge(
					$vendors,
					[
						[
							'value'    => $vendor->getId(),
							'name'     => $vendor->getName(),
							'selected' => $vendor->getId() === ( $profile ? $profile->get_vendor() : '' ),
						],
					]
				);
			}
		} catch ( \Exception $e ) {
			Logger::warning( "Can't load vendors", "Reason: {$e->getMessage()}" );
		}

		return $vendors;
	}

	/**
	 * Get errors repository
	 *
	 * @return ProfileRepository|null
	 */
	private static function get_repository() {
		$container = self::get_container();

		try {
			return $container->get( 'entity.repository.profile' );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Get Smartcat client service
	 * WTF?! Why I can't use Connector::set_core_parameters WordPress?! TELL ME!!!
	 *
	 * @return SmartCAT|null
	 */
	private static function get_smartcat() {
		$container = self::get_container();

		try {
			$options = $container->get( 'core.options' );
			$container->setParameter( 'smartcat.api.login', $options->get_and_decrypt( 'smartcat_api_login' ) );
			$container->setParameter( 'smartcat.api.password', $options->get_and_decrypt( 'smartcat_api_password' ) );
			$container->setParameter( 'smartcat.api.server', $options->get( 'smartcat_api_server' ) );
		} catch ( \Exception $e ) {
			Logger::warning( "Can't set core parameters", "Reason: {$e->getMessage()} {$e->getTraceAsString()}" );
		}

		try {
			return $container->get( 'smartcat' );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Get Language converter
	 *
	 * @return LanguageConverter|null
	 */
	private static function get_language_converter() {
		$container = self::get_container();

		try {
			return $container->get( 'language.converter' );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
