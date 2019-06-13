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

namespace SmartCAT\WP\Admin\Tables;

use SmartCAT\WP\DB\Entity\Profile;

/**
 * Class ProfilesTable
 *
 * @package SmartCAT\WP\Admin\Tables
 */
class ProfilesTable extends TableAbstract {
	/**
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'name'             => __( 'Name', 'translation-connectors' ),
			'source_language'  => __( 'Source language', 'translation-connectors' ),
			'target_languages' => __( 'Target languages', 'translation-connectors' ),
			'vendor_name'      => __( 'Vendor', 'translation-connectors' ),
			'workflow_stages'  => __( 'Edit post', 'translation-connectors' ),
			'auto_send'        => __( 'Auto send', 'translation-connectors' ),
			'auto_update'      => __( 'Auto update', 'translation-connectors' ),
		];

		return $columns;
	}

	/**
	 * @param Profile $item
	 * @param string $column_name
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
				return $item->get_name();
			case 'source_language':
				return $item->get_source_language();
			case 'target_languages':
				return implode( ',', $item->get_target_languages() );
			case 'vendor_name':
				return $item->get_vendor_name();
			case 'workflow_stages':
				return implode( ',', $item->get_workflow_stages() );
			case 'auto_send':
				$message = $item->is_auto_send() ? 'Yes' : 'No';

				return $message;
			case 'auto_update':
				$message = $item->is_auto_update() ? 'Yes' : 'No';

				return $message;
			default:
				return null;
		}
	}
}
