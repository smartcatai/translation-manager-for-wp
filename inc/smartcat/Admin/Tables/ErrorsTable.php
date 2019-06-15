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

use SmartCAT\WP\DB\Entity\Error;

/**
 * Class ErrorsTable
 *
 * @package SmartCAT\WP\Admin\Tables
 */
class ErrorsTable extends TableAbstract {
	/**
	 * Get columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'id'           => __( 'ID', 'translation-connectors' ),
			'date'         => __( 'Date', 'translation-connectors' ),
			'type'         => __( 'Type', 'translation-connectors' ),
			'shortMessage' => __( 'Short message', 'translation-connectors' ),
			'message'      => __( 'Message', 'translation-connectors' ),
		];

		return $columns;
	}

	/**
	 * Set data in columns
	 *
	 * @param Error  $item Item.
	 * @param string $column_name Column name.
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return $item->get_id();
			case 'date':
				return $item->get_date()->format( 'Y-m-d H:i:s' );
			case 'type':
				return ucfirst( $item->get_type() );
			case 'shortMessage':
				return $item->get_short_message();
			case 'message':
				return $item->get_message();
			default:
				return null;
		}
	}
}
