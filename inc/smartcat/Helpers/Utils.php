<?php

namespace SmartCAT\WP\Helpers;

//вспомогательные функции
use SmartCAT\WP\DITrait;
use SmartCAT\WP\WP\Options;

class Utils {
	use DITrait;

	public function is_array_in_array( $needle, $haystack ) {
		if ( ! is_array( $needle ) || ! is_array( $haystack ) ) {
			return false;
		}

		$intersect = array_intersect( $needle, $haystack );

		return (bool) ( count( $intersect ) == count( $needle ) );
	}

	public function get_pll_languages() {
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_names = pll_languages_list( [ 'fields' => 'name' ] );
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_locales = pll_languages_list( [ 'fields' => 'locale' ] );

		return array_combine( $pll_locales, $pll_names );
	}

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

	public function get_url_to_post_by_post_id( $post_id ) {
		return get_edit_post_link( $post_id );
	}
}