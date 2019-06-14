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
		$profile = [];
		self::set_core_parameters();

		if ( ! empty( $_POST ) ) {
			$verify_nonce = wp_verify_nonce(
				wp_unslash( sanitize_key( $_POST['sc_profile_wpnonce'] ?? null ) ),
				'sc_profile_edit'
			);

			if ( $verify_nonce ) {
				$sanitized_post = sanitize_post( $_POST, 'db' );
				self::edit_post( $sanitized_post );
			}
		}

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
				'texts'           => self::get_texts( $profile ),
				'profile'         => $profile,
				'sc_nonce'        => wp_create_nonce( 'sc_profile_edit' ),
				'workflow_stages' => self::get_workflow_stages( $profile ),
				'source_lang'     => self::get_languages( $profile, 'source_lang' ),
				'target_langs'    => self::get_languages( $profile, 'target_langs' ),
				'vendors'         => self::get_vendors( $profile ),
			]
		);
	}

	/**
	 * Get texts array for render
	 *
	 * @return array
	 */
	private static function get_texts( $profile ) {
		if ( empty( $profile ) ) {
			$title = __( 'New Profile', 'translation-connectors' );
		} else {
			$title = __( 'Edit Profile', 'translation-connectors' ) . " '{$profile['name']}'";
		}

		return [
			'empty'                   => __( 'Profiles not found', 'translation-connectors' ),
			'pages'                   => __( 'Pages', 'translation-connectors' ),
			'title'                   => $title,
			'profile_name'            => __( 'Name', 'translation-connectors' ),
			'profile_vendor'          => __( 'Vendor', 'translation-connectors' ),
			'profile_source_lang'     => __( 'Source Language', 'translation-connectors' ),
			'profile_target_langs'    => __( 'Target Languages', 'translation-connectors' ),
			'profile_workflow_stages' => __( 'Workflow Stages', 'translation-connectors' ),
			'profile_project_id'      => __( 'Project id', 'translation-connectors' ),
			'profile_name_note'       => __( 'Leave this field blank for automatic name generation', 'translation-connectors' ),
			'profile_auto_update'     => __( 'Auto update edited post', 'translation-connectors' ),
			'profile_auto_send'       => __( 'Auto send created post', 'translation-connectors' ),
		];
	}

	/**
	 * @param $profile
	 *
	 * @return array
	 */
	private static function get_workflow_stages( $profile ) {
		return [
			[
				'value'    => 'Translation',
				'name'     => __( 'Translation', 'translation-connectors' ),
				'selected' => ! empty( $profile ) ? in_array( 'Translation', $profile['workflow_stages'] ?? [], true ) : true,
			],
			[
				'value'    => 'Editing',
				'name'     => __( 'Editing', 'translation-connectors' ),
				'selected' => in_array( 'Editing', $profile['workflow_stages'] ?? [], true ),
			],
			[
				'value'    => 'Proofreading',
				'name'     => __( 'Proofreading', 'translation-connectors' ),
				'selected' => in_array( 'Proofreading', $profile['workflow_stages'] ?? [], true ),
			],
		];
	}

	/**
	 * @param $profile
	 * @param $key
	 *
	 * @return array
	 * @throws \Exception
	 */
	private static function get_languages( $profile, $key) {
		$languages = [];
		$container = self::get_container();

		/** @var LanguageConverter $language_converter */
		$language_converter = $container->get( 'language.converter' );
		$polylang_langs     = $language_converter->get_polylang_languages_supported_by_sc();

		foreach ( $polylang_langs as $locale => $name ) {
			if ( is_array( $profile[ $key ] ?? null ) ) {
				$search_array = $profile[ $key ];
			} else {
				if ( ! empty( $profile[ $key ] ) ) {
					$search_array = [ $profile[ $key ] ];
				} else {
					$search_array = [];
				}
			}

			$languages[] = [
				'value'    => $language_converter->get_sc_code_by_wp( $locale )->get_sc_code(),
				'name'     => $name,
				'selected' => in_array( $locale, $search_array, true ),
			];
		}

		return $languages;
	}

	/**
	 * WTF?! Why I can't use Connector::set_core_parameters WordPress?! TELL ME!!!
	 */
	private static function set_core_parameters() {
		$container = self::get_container();

		try {
			$options = $container->get( 'core.options' );
			$container->setParameter( 'smartcat.api.login', $options->get_and_decrypt( 'smartcat_api_login' ) );
			$container->setParameter( 'smartcat.api.password', $options->get_and_decrypt( 'smartcat_api_password' ) );
			$container->setParameter( 'smartcat.api.server', $options->get( 'smartcat_api_server' ) );
		} catch ( \Exception $e ) {
			Logger::warning( "Can't set core parameters in {$e->getFile()}:{$e->getLine()}" );
		}
	}

	/**
	 * @param array $profile
	 *
	 * @return array
	 */
	private static function get_vendors( $profile ) {
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
							'selected' => $vendor->getId() === ( $profile['vendor'] ?? '' ),
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
	 *
	 * @return SmartCAT|null
	 */
	private static function get_smartcat() {
		$container = self::get_container();

		try {
			return $container->get( 'smartcat' );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Create or update profile
	 *
	 * @param array $data Post array data.
	 */
	private static function edit_post( $data ) {
		$profiles_repo = self::get_repository();

		if ( ! empty( $data['profile_id'] ) ) {
			$profile = $profiles_repo->get_one_by_id( $data['profile_id'] );
		} else {
			$profile = new Profile();
		}

		$profile
			->set_name( $data['profile_name'] )
			->set_vendor( $data['profile_vendor'] )
			->set_vendor_name( __( 'Translate internally', 'translation-connectors' ) )
			->set_source_language( $data['profile_source_lang'] )
			->set_target_languages( $data['profile_target_langs'] )
			->set_project_id( $data['profile_project_id'] )
			->set_workflow_stages( $data['profile_workflow_stages'] )
			->set_auto_update( $data['profile_auto_update'] ?? false )
			->set_auto_send( $data['profile_auto_send'] ?? false );

		try {
			$vendor_list = wp_cache_get( 'vendor_list', 'translation-connectors' );

			if ( ! $vendor_list ) {
				$vendor_list = self::get_smartcat()->getDirectoriesManager()->directoriesGet( [ 'type' => 'vendor' ] )->getItems();
				wp_cache_set( 'vendor_list', $vendor_list, 'translation-connectors', 3600 );
			}

			foreach ( $vendor_list as $vendor ) {
				if ( $data['profile_vendor'] === $vendor->getId() ) {
					$profile->set_vendor_name( $vendor->getName() );
				}
			}
		} catch ( \Exception $e ) {
			Logger::warning( "Can't set vendor name", "Reason: {$e->getMessage()}" );
		}

		$profiles_repo->save( $profile );
	}
}
