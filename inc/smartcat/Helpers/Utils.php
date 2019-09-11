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

use SmartCat\Client\Model\DirectoryItemModel;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Repository\ProfileRepository;
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

		return (bool) ( count( $intersect ) === count( $needle ) );
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
		if ( function_exists( 'has_blocks' ) && ! has_blocks( $post_id ) ) {
			$post_body = wpautop( $post_body );
		} elseif ( ! function_exists( 'has_blocks' ) ) {
			$post_body = wpautop( $post_body );
		}

		$replace_count = 0;
		$iteration     = 0;

		do {
			$post_body = preg_replace_callback(
				'%\[([\w]+)(\s+.+?)?\]((.*?)\[\/\1\])?%s',
				function( $matches ) {
					$single = empty( $matches[4] ) ? 'true' : 'false';
					$unique_id = substr(preg_replace("%\d%", '', base_convert(sha1(uniqid(mt_rand(), true)), 16, 36)), 0, 7);
					return "<sc-shortcode-{$unique_id} sc-type=\"{$matches[1]}\" sc-single=\"{$single}\"{$matches[2]}>{$matches[4]}</sc-shortcode-{$unique_id}>";
				},
				$post_body,
				-1,
				$replace_count
			);

			$iteration++;

			if ( $iteration >= 50 ) {
				Logger::warning( 'Limit exceeded', "Shortcodes replacing iteration limit exceeded in post '{$post->post_title}'" );
			}
		} while ( $replace_count && ( $iteration < 50 ) );

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
		$prefix = Connector::get_container()->getParameter( 'plugin.table.prefix' );
		return get_option( $prefix . 'smartcat_db_version', '1.0.0' );
	}

	/**
	 * @param $str
	 * @param $start
	 * @param null $length
	 *
	 * @return string
	 */
	public static function substr_unicode( $str, $start, $length = null ) {
		return join(
			'',
			array_slice( preg_split( '//u', $str, -1, PREG_SPLIT_NO_EMPTY ), $start, $length )
		);
	}

	/**
	 * @param SmartCAT $smartcat
	 */
	public static function check_vendor_exists( $smartcat ) {
		try {
			/** @var ProfileRepository $profiles_repository */
			$profiles_repository = self::get_container()->get( 'entity.repository.profile' );
			$profiles            = $profiles_repository->get_all();

			$sc_vendors = array_map(
				function ( DirectoryItemModel $directory ) {
					return $directory->getId();
				},
				$smartcat->getDirectoriesManager()->directoriesGet( [ 'type' => 'vendor' ] )->getItems()
			);
		} catch (\Exception $e) {
			throw $e;
		}

		foreach ( $profiles as $profile ) {
			if ( ! in_array( $profile->get_vendor(), $sc_vendors, true ) ) {
				throw new \Exception(
					__( 'The changes have not been saved.', 'translation-connectors' ) . '<br />' .
					__( 'Some of your profiles contain a vendor that is not in your Smartcat account.', 'translation-connectors' ) . '<br />' .
					__( 'Please edit the profile or add this vendor to your Smartcat account.', 'translation-connectors' )
				);
			}
		}
	}
}
