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

namespace SmartCAT\WP\Helpers;

//вспомогательные функции
use SmartCAT\WP\DITrait;
use SmartCAT\WP\WP\Options;

class Utils {
	use DITrait;

	/**
	 * @param $needle
	 * @param $haystack
	 * @return bool
	 */
	public function is_array_in_array( $needle, $haystack ) {
		if ( ! is_array( $needle ) || ! is_array( $haystack ) ) {
			return false;
		}

		$intersect = array_intersect( $needle, $haystack );

		return ( bool ) ( count( $intersect ) == count( $needle ) );
	}

	/**
	 * @return array|false
	 */
	public function get_pll_languages() {
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_names = pll_languages_list( [ 'fields' => 'name' ] );
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_locales = pll_languages_list( [ 'fields' => 'locale' ] );

		return array_combine( $pll_locales, $pll_names );
	}

	/**
	 * @param $document_id_string
	 * @return string
	 * @throws \Exception
	 */
	public function get_url_to_smartcat_by_document_id( $document_id_string ) {
		$document_id = $document_id_string;
		$language_id = null;

		$container = self::get_container();
		/** @var Options $options */
		$options = $container->get( 'core.options' );
		$server  = $options->get( 'smartcat_api_server' );
		$server || $server = 'smartcat.ai';

		$result = 'https://' . $server;

		if ( strpos( $document_id_string, '_' ) !== false ) {
			list( $document_id, $language_id ) = explode( '_', $document_id_string );
		}

		$result .= "/editor?DocumentId={$document_id}";

		if ( ! empty( $language_id ) ) {
			$result .= "&LanguageId={$language_id}";
		}

		if ( $account_name = $options->get( 'smartcat_account_name' ) ) {
			$result .= '&AccountName=' . $account_name;
		}

		return $result;
	}

	/**
	 * @param $post_id
	 * @return string|null
	 */
	public function get_url_to_post_by_post_id( $post_id ) {
		return get_edit_post_link( $post_id );
	}

	/**
	 * @param $post_id
	 *
	 * @return bool|resource
	 */
	public function get_post_to_file( $post_id ) {
		$post      = get_post( $post_id );
		$post_body = $post->post_content;

		// Gutenberg fix for WP 4.9.* and lower.
		if ( ! function_exists( 'has_blocks' ) || ! has_blocks( $post_id ) ) {
			$post_body = wpautop( $post_body );
		}

		$file_body = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" /><title>{$post->post_title}</title></head><body>{$post_body}</body></html>";
		$file      = fopen( "php://temp/{$post->post_title}.html", 'r+' );
		fwrite( $file, $file_body );
		rewind( $file );

		return $file;
	}

	/**
	 * @return int
	 */
	public static function get_plugin_version_file() {
		if ( defined( 'SMARTCAT_PLUGIN_FILE' ) ) {
			$plugin_data = get_file_data( SMARTCAT_PLUGIN_FILE, [ 'Version' => 'Version' ] );
			return $plugin_data['Version'];
		}
		return 0;
	}

	/**
	 * @return mixed|void
	 */
	public static function get_plugin_version() {
		return get_option( 'smartcat_connector_smartcat_db_version', 0 );
	}
}
