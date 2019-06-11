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

namespace SmartCAT\WP\Helpers\Language;

use SmartCAT\WP\Helpers\Language\Exceptions\LanguageNotFoundException;

//TODO: возможно вынести отдельно релэйшены длы WP и SC по разным классам, данный класс оставить в качестве интерфейсного
final class LanguageConverter {
	protected $wp_to_sc_relations;
	protected $sc_to_wp_relations;

	//весь сырбор из-за требования обратной конвертации ( из SC в WP )
	protected function add_relation( $wp_code, $sc_code, $wp_name, $sc_name = null ) {
		//использую объект для передачи по ссылке в два места ( теоретически, сэкономит память )
		$language = new LanguageEntity( $wp_code, $sc_code, $wp_name, $sc_name );

		//две ассоциации для быстрой выборки, возможно от второй можно будет отказаться
		$this->wp_to_sc_relations[ $wp_code ]   = $language; // 1 к 1
		$this->sc_to_wp_relations[ $sc_code ][] = $language; // 1 ко многим
	}

	protected function init() {
		//отдельным методом, чтоб можно было подменить при необходимости
		$this->add_relation( 'af', 'af', __( 'Afrikaans', 'translation-connectors' ) );
		$this->add_relation( 'am', 'am', __( 'Amharic', 'translation-connectors' ) );
		$this->add_relation( 'ar', 'ar', __( 'Arabic', 'translation-connectors' ) );
		$this->add_relation( 'arq', 'ar', __( 'Algerian Arabic', 'translation-connectors' ) );
		$this->add_relation( 'ary', 'ar', __( 'Moroccan Arabic', 'translation-connectors' ) );
		$this->add_relation( 'as', 'as', __( 'Assamese', 'translation-connectors' ) );
		$this->add_relation( 'az', 'az', __( 'Azerbaijani', 'translation-connectors' ) );
		$this->add_relation( 'az_TR', 'az', __( 'Azerbaijani ( Turkey )', 'translation-connectors' ) );
		$this->add_relation( 'azb', 'az', __( 'South Azerbaijani', 'translation-connectors' ) );
		$this->add_relation( 'ba', 'ba', __( 'Bashkir', 'translation-connectors' ) );
		$this->add_relation( 'bel', 'be', __( 'Belarusian', 'translation-connectors' ) );
		$this->add_relation( 'bg_BG', 'bg', __( 'Bulgarian', 'translation-connectors' ) );
		$this->add_relation( 'bn_BD', 'bn', __( 'Bengali', 'translation-connectors' ) );
		$this->add_relation( 'bo', 'bo', __( 'Tibetan', 'translation-connectors' ) );
		$this->add_relation( 'bs_BA', 'bs', __( 'Bosnian', 'translation-connectors' ) );
		$this->add_relation( 'bal', 'ca', __( 'Catalan ( Balear )', 'translation-connectors' ) );
		$this->add_relation( 'ca', 'ca', __( 'Catalan', 'translation-connectors' ) );
		$this->add_relation( 'cs_CZ', 'cs', __( 'Czech', 'translation-connectors' ) );
		$this->add_relation( 'da_DK', 'da', __( 'Danish', 'translation-connectors' ) );
		$this->add_relation( 'de_CH', 'de-CH', __( 'German ( Switzerland )', 'translation-connectors' ) );
		$this->add_relation( 'de_DE', 'de-DE', __( 'German', 'translation-connectors' ) );
		$this->add_relation( 'el', 'el', __( 'Greek', 'translation-connectors' ) );
		$this->add_relation( 'en_CA', 'en', __( 'English ( Canada )', 'translation-connectors' ) );
		$this->add_relation( 'en_NZ', 'en', __( 'English ( New Zealand )', 'translation-connectors' ) );
		$this->add_relation( 'en_ZA', 'en', __( 'English ( South Africa )', 'translation-connectors' ) );
		$this->add_relation( 'en_AU', 'en-AU', __( 'English ( Australia )', 'translation-connectors' ) );
		$this->add_relation( 'en_GB', 'en-GB', __( 'English ( UK )', 'translation-connectors' ) );
		$this->add_relation( 'en_US', 'en-US', __( 'English', 'translation-connectors' ) );
		$this->add_relation( 'eo', 'eo', __( 'Esperanto', 'translation-connectors' ) );
		$this->add_relation( 'es_CL', 'es', __( 'Spanish ( Chile )', 'translation-connectors' ) );
		$this->add_relation( 'es_CO', 'es', __( 'Spanish ( Colombia )', 'translation-connectors' ) );
		$this->add_relation( 'es_GT', 'es', __( 'Spanish ( Guatemala )', 'translation-connectors' ) );
		$this->add_relation( 'es_PE', 'es', __( 'Spanish ( Peru )', 'translation-connectors' ) );
		$this->add_relation( 'es_PR', 'es', __( 'Spanish ( Puerto Rico )', 'translation-connectors' ) );
		$this->add_relation( 'es_VE', 'es', __( 'Spanish ( Venezuela )', 'translation-connectors' ) );
		$this->add_relation( 'es_AR', 'es-AR', __( 'Spanish ( Argentina )', 'translation-connectors' ) );
		$this->add_relation( 'es_ES', 'es-ES', __( 'Spanish ( Spain )', 'translation-connectors' ) );
		$this->add_relation( 'es_MX', 'es-MX', __( 'Spanish ( Mexico )', 'translation-connectors' ) );
		$this->add_relation( 'et', 'et', __( 'Estonian', 'translation-connectors' ) );
		$this->add_relation( 'eu', 'eu', __( 'Basque', 'translation-connectors' ) );
		$this->add_relation( 'fi', 'fi', __( 'Finnish', 'translation-connectors' ) );
		$this->add_relation( 'fr_BE', 'fr', __( 'French ( Belgium )', 'translation-connectors' ) );
		$this->add_relation( 'fr_CA', 'fr-CA', __( 'French ( Canada )', 'translation-connectors' ) );
		$this->add_relation( 'fr_FR', 'fr-FR', __( 'French ( France )', 'translation-connectors' ) );
		$this->add_relation( 'ga', 'ga', __( 'Irish', 'translation-connectors' ) );
		$this->add_relation( 'gl_ES', 'gl', __( 'Galician', 'translation-connectors' ) );
		$this->add_relation( 'gn', 'gn', __( 'Guaraní', 'translation-connectors' ) );
		$this->add_relation( 'gu', 'gu', __( 'Gujarati', 'translation-connectors' ) );
		$this->add_relation( 'he_IL', 'he', __( 'Hebrew', 'translation-connectors' ) );
		$this->add_relation( 'hi_IN', 'hi', __( 'Hindi', 'translation-connectors' ) );
		$this->add_relation( 'hr', 'hr', __( 'Croatian', 'translation-connectors' ) );
		$this->add_relation( 'hu_HU', 'hu', __( 'Hungarian', 'translation-connectors' ) );
		$this->add_relation( 'hy', 'hy', __( 'Armenian', 'translation-connectors' ) );
		$this->add_relation( 'id_ID', 'id', __( 'Indonesian', 'translation-connectors' ) );
		$this->add_relation( 'is_IS', 'is', __( 'Icelandic', 'translation-connectors' ) );
		$this->add_relation( 'it_IT', 'it', __( 'Italian', 'translation-connectors' ) );
		$this->add_relation( 'ja', 'ja', __( 'Japanese', 'translation-connectors' ) );
		$this->add_relation( 'jv_ID', 'jv', __( 'Javanese', 'translation-connectors' ) );
		$this->add_relation( 'ka_GE', 'ka', __( 'Georgian', 'translation-connectors' ) );
		$this->add_relation( 'kk', 'kk', __( 'Kazakh', 'translation-connectors' ) );
		$this->add_relation( 'km', 'km', __( 'Khmer', 'translation-connectors' ) );
		$this->add_relation( 'kn', 'kn', __( 'Kannada', 'translation-connectors' ) );
		$this->add_relation( 'ko_KR', 'ko', __( 'Korean', 'translation-connectors' ) );
		$this->add_relation( 'ckb', 'ku', __( 'Kurdish ( Sorani )', 'translation-connectors' ) );
		$this->add_relation( 'ky_KY', 'ky', __( 'Kirghiz', 'translation-connectors' ) );
		$this->add_relation( 'lb_LU', 'lb', __( 'Luxembourgish', 'translation-connectors' ) );
		$this->add_relation( 'lo', 'lo', __( 'Lao', 'translation-connectors' ) );
		$this->add_relation( 'lt_LT', 'lt', __( 'Lithuanian', 'translation-connectors' ) );
		$this->add_relation( 'lv', 'lv', __( 'Latvian', 'translation-connectors' ) );
		$this->add_relation( 'mg_MG', 'mg', __( 'Malagasy', 'translation-connectors' ) );
		$this->add_relation( 'mk_MK', 'mk', __( 'Macedonian', 'translation-connectors' ) );
		$this->add_relation( 'ml_IN', 'ml', __( 'Malayalam', 'translation-connectors' ) );
		$this->add_relation( 'mn', 'mn', __( 'Mongolian', 'translation-connectors' ) );
		$this->add_relation( 'mr', 'mr', __( 'Marathi', 'translation-connectors' ) );
		$this->add_relation( 'ms_MY', 'ms', __( 'Malay', 'translation-connectors' ) );
		$this->add_relation( 'my_MM', 'my', __( 'Myanmar ( Burmese )', 'translation-connectors' ) );
		$this->add_relation( 'nb_NO', 'nb', __( 'Norwegian ( Bokmål )', 'translation-connectors' ) );
		$this->add_relation( 'ne_NP', 'ne', __( 'Nepali', 'translation-connectors' ) );
		$this->add_relation( 'nl_BE', 'nl', __( 'Dutch ( Belgium )', 'translation-connectors' ) );
		$this->add_relation( 'nl_NL', 'nl', __( 'Dutch', 'translation-connectors' ) );
		$this->add_relation( 'nn_NO', 'nn', __( 'Norwegian ( Nynorsk )', 'translation-connectors' ) );
		$this->add_relation( 'os', 'os', __( 'Ossetic', 'translation-connectors' ) );
		$this->add_relation( 'pa_IN', 'pa', __( 'Punjabi', 'translation-connectors' ) );
		$this->add_relation( 'pl_PL', 'pl', __( 'Polish', 'translation-connectors' ) );
		$this->add_relation( 'ps', 'ps', __( 'Pashto', 'translation-connectors' ) );
		$this->add_relation( 'pt_BR', 'pt-BR', __( 'Portuguese ( Brazil )', 'translation-connectors' ) );
		$this->add_relation( 'pt_PT', 'pt-PT', __( 'Portuguese ( Portugal )', 'translation-connectors' ) );
		$this->add_relation( 'ro_RO', 'ro', __( 'Romanian', 'translation-connectors' ) );
		$this->add_relation( 'ru_RU', 'ru', __( 'Russian', 'translation-connectors' ) );
		$this->add_relation( 'sa_IN', 'sa', __( 'Sanskrit', 'translation-connectors' ) );
		$this->add_relation( 'sah', 'sah', __( 'Sakha', 'translation-connectors' ) );
		$this->add_relation( 'si_LK', 'si', __( 'Sinhala', 'translation-connectors' ) );
		$this->add_relation( 'sk_SK', 'sk', __( 'Slovak', 'translation-connectors' ) );
		$this->add_relation( 'sl_SI', 'sl', __( 'Slovenian', 'translation-connectors' ) );
		$this->add_relation( 'so_SO', 'so', __( 'Somali', 'translation-connectors' ) );
		$this->add_relation( 'sq', 'sq', __( 'Albanian', 'translation-connectors' ) );
		$this->add_relation( 'sr_RS', 'sr-Latn', __( 'Serbian', 'translation-connectors' ) );
		$this->add_relation( 'sv_SE', 'sv', __( 'Swedish', 'translation-connectors' ) );
		$this->add_relation( 'sw', 'sw', __( 'Swahili', 'translation-connectors' ) );
		$this->add_relation( 'ta_IN', 'ta', __( 'Tamil', 'translation-connectors' ) );
		$this->add_relation( 'ta_LK', 'ta', __( 'Tamil ( Sri Lanka )', 'translation-connectors' ) );
		$this->add_relation( 'te', 'te', __( 'Telugu', 'translation-connectors' ) );
		$this->add_relation( 'tg', 'tg', __( 'Tajik', 'translation-connectors' ) );
		$this->add_relation( 'th', 'th', __( 'Thai', 'translation-connectors' ) );
		$this->add_relation( 'tir', 'ti', __( 'Tigrinya', 'translation-connectors' ) );
		$this->add_relation( 'tuk', 'tk', __( 'Turkmen', 'translation-connectors' ) );
		$this->add_relation( 'tl', 'tl', __( 'Tagalog', 'translation-connectors' ) );
		$this->add_relation( 'tr_TR', 'tr', __( 'Turkish', 'translation-connectors' ) );
		$this->add_relation( 'tt_RU', 'tt', __( 'Tatar', 'translation-connectors' ) );
		$this->add_relation( 'ug_CN', 'ug', __( 'Uighur', 'translation-connectors' ) );
		$this->add_relation( 'uk', 'uk', __( 'Ukrainian', 'translation-connectors' ) );
		$this->add_relation( 'ur', 'ur', __( 'Urdu', 'translation-connectors' ) );
		$this->add_relation( 'uz_UZ', 'uz-Latn', __( 'Uzbek', 'translation-connectors' ) );
		$this->add_relation( 'vi', 'vi', __( 'Vietnamese', 'translation-connectors' ) );
		$this->add_relation( 'zh_CN', 'zh-Hans', __( 'Chinese ( China )', 'translation-connectors' ) );
		$this->add_relation( 'zh_HK', 'zh-Hant-HK', __( 'Chinese ( Hong Kong )', 'translation-connectors' ) );
		$this->add_relation( 'zh_TW', 'zh-Hant-TW', __( 'Chinese ( Taiwan )', 'translation-connectors' ) );
		$this->add_relation( 'ak', 'ak', __( 'Akan', 'translation-connectors' ) );
		$this->add_relation( 'bcc', 'bcc', __( 'Balochi Southern', 'translation-connectors' ) );
		$this->add_relation( 'ceb', 'ceb', __( 'Cebuano', 'translation-connectors' ) );
		$this->add_relation( 'co', 'it', __( 'Corsican', 'translation-connectors' ) );
		$this->add_relation( 'fa_AF', 'fa', __( 'Persian ( Afghanistan )', 'translation-connectors' ) );
		$this->add_relation( 'fa_IR', 'fa', __( 'Persian', 'translation-connectors' ) );
		$this->add_relation( 'fuc', 'ff', __( 'Fulah', 'translation-connectors' ) );
		$this->add_relation( 'gsw', 'de-CH', __( 'Swiss German', 'translation-connectors' ) );
		$this->add_relation( 'haz', 'haz', __( 'Hazaragi', 'translation-connectors' ) );
		$this->add_relation( 'kab', 'kab', __( 'Kabyle', 'translation-connectors' ) );
		$this->add_relation( 'kin', 'rw', __( 'Kinyarwanda', 'translation-connectors' ) );
		$this->add_relation( 'li', 'li', __( 'Limburgish', 'translation-connectors' ) );
		$this->add_relation( 'lin', 'ln', __( 'Lingala', 'translation-connectors' ) );
		$this->add_relation( 'me_ME', 'sr-Latn', __( 'Montenegrin', 'translation-connectors' ) );
		$this->add_relation( 'oci', 'oc', __( 'Occitan', 'translation-connectors' ) );
		$this->add_relation( 'ory', 'or', __( 'Oriya', 'translation-connectors' ) );
		$this->add_relation( 'rhg', 'rhg-Latn', __( 'Rohingya', 'translation-connectors' ) );
		$this->add_relation( 'rue', 'uk', __( 'Rusyn', 'translation-connectors' ) );
		$this->add_relation( 'snd', 'sd', __( 'Sindhi', 'translation-connectors' ) );
		$this->add_relation( 'srd', 'sc', __( 'Sardinian', 'translation-connectors' ) );
		$this->add_relation( 'su_ID', 'su', __( 'Sundanese', 'translation-connectors' ) );
		$this->add_relation( 'szl', 'pl', __( 'Silesian', 'translation-connectors' ) );
		$this->add_relation( 'yor', 'yo', __( 'Yoruba', 'translation-connectors' ) );
	}

	public function __construct() {
		$this->init();
	}

	/**
	 * @param $wp_code
	 *
	 * @return LanguageEntity
	 * @throws LanguageNotFoundException
	 */
	public function get_sc_code_by_wp( $wp_code ) {
		if ( ! isset( $this->wp_to_sc_relations[ $wp_code ] ) ) {
			throw new LanguageNotFoundException( __( 'Not found sc lang by wp_code = ' . $wp_code,
				'translation-connectors' ) );
		}

		return $this->wp_to_sc_relations[ $wp_code ];
	}

	/**
	 * @param $sc_code
	 *
	 * @return LanguageEntity
	 * @throws LanguageNotFoundException
	 */
	public function get_wp_code_by_sc( $sc_code ) {
		if ( ! isset( $this->sc_to_wp_relations[ $sc_code ] ) || ! isset( $this->sc_to_wp_relations[ $sc_code ][0] ) ) {
			throw new LanguageNotFoundException( __( 'Not found wp lang by sc_code = ' . $sc_code,
				'translation-connectors' ) );
		}

		return $this->sc_to_wp_relations[ $sc_code ][0]; //возвращаем первый попавшийся до новых требований
	}

	public function get_all_wp_codes_by_sc( $sc_code ) {
		if ( ! isset( $this->sc_to_wp_relations[ $sc_code ] ) || ! is_array( $this->sc_to_wp_relations[ $sc_code ] ) ) {
			throw new LanguageNotFoundException( __( 'Not found wp lang by sc_code = ' . $sc_code,
				'translation-connectors' ) );
		}

		return $this->sc_to_wp_relations[ $sc_code ]; //возвращаем весь массив
	}

	public function get_all_sc_languages() {
		return $this->sc_to_wp_relations;
	}

	public function get_all_wp_languages() {
		return $this->wp_to_sc_relations;
	}

	public function get_sc_codes() {
		return array_keys( $this->sc_to_wp_relations );
	}

	public function get_wp_codes() {
		return array_keys( $this->wp_to_sc_relations );
	}

	public function get_wp_languages() {
		$result = array_map( function (
			/** @var LanguageEntity $value */
			$value
		) {
			return $value->get_wp_name();
		}, $this->wp_to_sc_relations );

		asort( $result );

		return $result;
	}

	public function get_sc_languages() {
		$result = array_map( function (
			/** @var LanguageEntity[] $value */
			$value
		) {
			return $value[0]->get_sc_name();
		}, $this->sc_to_wp_relations );

		asort( $result );

		return $result;
	}

	public function get_wp_target_languages( $source_language_code ) {
		$languages = $this->get_wp_languages();
		if ( isset( $languages[ $source_language_code ] ) ) {
			unset( $languages[ $source_language_code ] );
		}

		return $languages;
	}


	public function get_sc_target_languages( $source_language_code ) {
		$languages = $this->get_sc_languages();
		if ( isset( $languages[ $source_language_code ] ) ) {
			unset( $languages[ $source_language_code ] );
		}

		return $languages;
	}

	public function get_polylang_names_to_locales() {
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_names = pll_languages_list( [ 'fields' => 'name' ] );
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_locales = pll_languages_list( [ 'fields' => 'locale' ] );

		$result = array_combine( $pll_locales, $pll_names );

		return $result;
	}

	public function get_polylang_names_to_slugs() {
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_names = pll_languages_list( [ 'fields' => 'name' ] );
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_slugs = pll_languages_list( [ 'fields' => 'slug' ] );

		$result = array_combine( $pll_names, $pll_slugs );

		return $result;
	}

	public function get_polylang_slugs_to_names() {
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_slugs = pll_languages_list( [ 'fields' => 'slug' ] );
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_names = pll_languages_list( [ 'fields' => 'name' ] );

		$result = array_combine( $pll_slugs, $pll_names );

		return $result;
	}

	public function get_polylang_language_name_by_slug( $slug )
	{
		$languages = $this->get_polylang_slugs_to_names();
		return $languages[$slug] ?? '';
	}

	//не классичный подход с возвращаемыми параметрами в интерфейсе метода, здесь мне показалось уместным и удобным
	public function get_polylang_languages_supported_by_sc( &$unsupported_languages_array = [] ) {
		$languages = $this->get_polylang_names_to_locales();

		//TODO: возможно, следует пересмотреть интерфейс и вместо эксепшена возвращать false ( будет быстрее )
		$result = [];
		foreach ( $languages as $locale => $name ) {
			try {
				$this->get_sc_code_by_wp( $locale );
			} catch ( LanguageNotFoundException $e ) {
				array_push( $unsupported_languages_array, $name );
				continue;
			}

			$result[ $locale ] = $name;
		}

		return $result;
	}

	//пришлось писать отдельную функцию для фронта
	public function get_polylang_slugs_supported_by_sc() {
		//TODO: далеко не самое оптимальное решение, мб назреет что-то более адекватное
		$name_to_slug	= $this->get_polylang_names_to_slugs();
		$name_to_locales = array_flip( $this->get_polylang_languages_supported_by_sc() );

		$result = [];
		foreach ( $name_to_locales as $name => $locale ) {
			$result[] = $name_to_slug[ $name ];
		}

		return $result;
	}

	public function get_polylang_slugs_to_locales() {
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_slug = pll_languages_list( [ 'fields' => 'slug' ] );
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_locale = pll_languages_list( [ 'fields' => 'locale' ] );

		$result = array_combine( $pll_slug, $pll_locale );

		return $result;
	}

	public function get_polylang_locales_to_slugs() {
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_slug = pll_languages_list( [ 'fields' => 'slug' ] );
		/** @noinspection PhpUndefinedFunctionInspection */
		$pll_locale = pll_languages_list( [ 'fields' => 'locale' ] );

		$result = array_combine( $pll_locale, $pll_slug );

		return $result;
	}
}