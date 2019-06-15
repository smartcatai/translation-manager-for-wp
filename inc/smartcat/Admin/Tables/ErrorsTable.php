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

use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Error;
use SmartCAT\WP\DB\Repository\ErrorRepository;

/**
 * Class ErrorsTable
 *
 * @package SmartCAT\WP\Admin\Tables
 */
class ErrorsTable extends TableAbstract {
	/**
	 * ErrorsTable constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'error',
				'plural'   => 'errors',
				'ajax'     => false,
			]
		);
	}

	/**
	 * Prepare items for display
	 */
	public function prepare_items() {
		$errors_repository = self::get_repository();
		$per_page          = get_user_meta( get_current_user_id(), 'sc_errors_per_page', true ) ?: 20;

		$this->set_pagination_args(
			[
				'total_items' => $errors_repository->get_count(),
				'per_page'    => intval( $per_page ),
			]
		);

		$cur_page = (int) $this->get_pagenum();
		$items    = $errors_repository->get_all(
			intval( $per_page ) * ( $cur_page - 1 ),
			intval( $per_page )
		);

		$this->set_data( $items );

		parent::prepare_items();
	}

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

	/**
	 * Get errors repository
	 *
	 * @return ErrorRepository|null
	 */
	private static function get_repository() {
		$container = Connector::get_container();

		try {
			return $container->get( 'entity.repository.error' );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
