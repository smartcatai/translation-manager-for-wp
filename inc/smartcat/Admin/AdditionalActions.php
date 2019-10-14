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

use SmartCAT\WP\WP\HookInterface;

/**
 * Class Columns
 *
 * @package SmartCAT\WP\Admin
 */
final class AdditionalActions implements HookInterface {
	/**
	 * @param $bulk_actions
	 *
	 * @return mixed
	 */
	public static function register_bulk_actions( $bulk_actions ) {
		$bulk_actions['send_to_smartcat'] = __( 'Translate', 'translation-connectors' );

		return $bulk_actions;
	}

	/**
	 * @param array $actions
	 * @param \WP_Post $page_object
	 *
	 * @return mixed
	 */
	public static function translate_row_action( $actions, $page_object ) {
		$link_text         = __( 'Translate', 'translation-connectors' );
		$pll_slugs         = pll_languages_list( [ 'fields' => 'slug' ] );
		$translation_posts = [];
		$translation_slugs = [];

		foreach ( $pll_slugs as $slug ) {
			$post = pll_get_post( $page_object->ID, $slug );
			if ( $post ) {
				$translation_posts[] = $post;
				$translation_slugs[] = $slug;
			}
		}

		$attributes = [
			'data_post_id'           => esc_attr( $page_object->ID ),
			'id'                     => esc_attr( "translation-connectors-{$page_object->ID}" ),
			'data-author'            => esc_attr( get_the_author_meta( 'nicename' ) ),
			'data-post-pll-slugs'    => esc_attr( implode( ',', $pll_slugs ) ),
			'data-translation-slugs' => esc_attr( implode( ',', $translation_slugs ) ),
		];

		$attributes_string = self::make_attributes_string( $attributes );

		if ( count( $translation_posts ) !== count( $pll_slugs ) ) {
			$actions['submit-for-translation'] = "<a class='send-to-smartcat-anchor' href='#' $attributes_string> {$link_text} </a>";
		}

		return $actions;
	}

	/**
	 * @param $array
	 *
	 * @return string
	 */
	private static function make_attributes_string( $array ) {
		$result = ' ';
		foreach ( $array as $key => $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}
			$value   = esc_attr( $value );
			$result .= "{$key}='{$value}'";
		}

		return $result;
	}

	/**
	 * @return mixed|void
	 */
	public function register_hooks() {
		add_filter( 'post_row_actions', [ self::class, 'translate_row_action' ], 10, 2 );
		add_filter( 'page_row_actions', [ self::class, 'translate_row_action' ], 10, 2 );
		add_filter( 'bulk_actions-edit-page', [ self::class, 'register_bulk_actions' ] );
		add_filter( 'bulk_actions-edit-post', [ self::class, 'register_bulk_actions' ] );
	}
}
