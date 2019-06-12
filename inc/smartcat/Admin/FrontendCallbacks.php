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

use SmartCAT\WP\DITrait;
use SmartCAT\WP\Helpers\TemplateEngine;

/**
 * Class FrontendCallbacks
 *
 * @package SmartCAT\WP\Admin
 */
class FrontendCallbacks {
	use DITrait;

	/**
	 * @param $args
	 */
	public static function input_text_callback( $args ) {
		$option_name = $args['option_name'];
		$option      = get_option( $option_name );
		$type        = isset( $args['type'] ) ? esc_attr( $args['type'] ) : 'text';

		echo self::render(
			'partials/input',
			[
				'option'      => $type === 'password' && ! empty( $option ) ? '******' : $option,
				'type'        => $type,
				'option_name' => $option_name,
				'label_for'   => $args['label_for'],
			]
		);
	}

	/**
	 * @param $args
	 */
	public static function input_checkbox_callback( $args ) {
		$option  = get_option( $args['option_name'] );
		$options = [];

		foreach ( $args['checkboxes_list'] as $checkbox_value => $checkbox_text ) {
			$options[] = [
				'option_name'    => $args['option_name'],
				'checkbox_value' => $checkbox_value,
				'checkbox_text'  => __( $checkbox_text, 'translation-connectors' ),
				'checked'        => in_array( $checkbox_value, ! $option ? [ 'Translation' ] : $option, true ) ? 'checked' : '',
			];
		}

		echo self::render( 'partials/checkbox', [ 'checkboxes_list' => $options ] );
	}

	/**
	 * @param $args
	 */
	public static function select_callback( $args ) {
		$options = [];

		foreach ( $args['select_options'] as $select_option_value => $select_option_name ) {
			$options[] = [
				'select_option_value' => $select_option_value,
				'select_option_name'  => $select_option_name,
				'selected'            => selected( get_option( $args['option_name'] ), $select_option_value, false ),
			];
		}

		echo self::render(
			'partials/select',
			[
				'label_for'      => $args['label_for'],
				'option_name'    => $args['option_name'],
				'select_options' => $options,
			]
		);
	}

	/**
	 * @param $args
	 */
	public static function input_radio_callback( $args ) {
		//на случай добавления радиобаттонов, пока не используется
	}

	/**
	 * Used for dummy section callbacks
	 */
	public static function dummy_callback() {
	}

	/**
	 * @param string $template
	 * @param array $context
	 *
	 * @return string
	 * @throws \Exception
	 */
	private static function render( string $template, array $context ) {
		$container = self::get_container();

		/** @var TemplateEngine $renderer */
		$renderer = $container->get( 'templater' );

		return $renderer->render( $template, $context );
	}
}
