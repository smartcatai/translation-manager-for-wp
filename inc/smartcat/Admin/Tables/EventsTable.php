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
use SmartCAT\WP\DB\Entity\Event;
use SmartCAT\WP\DB\Repository\EventRepository;

/**
 * Class EventsTable
 *
 * @package SmartCAT\WP\Admin\Tables
 */
class EventsTable extends TableAbstract {
	/**
	 * ErrorsTable constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'event',
				'plural'   => 'events',
				'ajax'     => false,
			]
		);

		$this->bulk_action_handler();
	}

	/**
	 * Prepare items for display
	 */
	public function prepare_items() {
		$event_repository = self::get_repository();
		$per_page         = get_user_meta( get_current_user_id(), 'sc_events_per_page', true ) ?: 50;

		$this->set_pagination_args(
			[
				'total_items' => $event_repository->get_count(),
				'per_page'    => intval( $per_page ),
			]
		);

		$cur_page = (int) $this->get_pagenum();
		$items    = $event_repository->get_all(
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
			'cb'           => '<input type="checkbox" />',
			'id'           => __( 'ID', 'translation-connectors' ),
			'date'         => __( 'Date', 'translation-connectors' ),
			'type'         => __( 'Type', 'translation-connectors' ),
			'message'      => __( 'Message', 'translation-connectors' ),
		];

		return $columns;
	}

	/**
	 * Set data in columns
	 *
	 * @param Event  $item Item.
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
			case 'message':
				return $item->get_message();
			default:
				return null;
		}
	}

	/**
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = [
			'bulk-delete-' . $this->_args['plural'] => __( 'Delete', 'translation-connectors' ),
		];

		return $actions;
	}

	/**
	 * Bulk actions handler
	 */
	private function bulk_action_handler() {
		if ( empty( $_POST[ $this->_args['plural'] ] ) || empty( $_POST['_wpnonce'] ) ) {
			return;
		}

		$action       = $this->current_action();
		$verify_nonce = wp_verify_nonce(
			wp_unslash( sanitize_key( $_POST['_wpnonce'] ) ),
			'bulk-' . $this->_args['plural']
		);

		if ( ! $action || ! $verify_nonce ) {
			return;
		}

		$post        = sanitize_post( $_POST, 'db' );
		$errors_repo = self::get_repository();

		switch ( $action ) {
			case 'bulk-delete-' . $this->_args['plural']:
				foreach ( $post[ $this->_args['plural'] ] as $statistic_id ) {
					$errors_repo->delete_by_id( $statistic_id );
				}
				break;
		}
	}

	/**
	 * Get event repository
	 *
	 * @return EventRepository|null
	 */
	private static function get_repository() {
		$container = Connector::get_container();

		try {
			return $container->get( 'entity.repository.event' );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
