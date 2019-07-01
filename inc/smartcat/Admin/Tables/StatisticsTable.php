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
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\Helpers\Language\LanguageConverter;
use SmartCAT\WP\Helpers\Utils;

/**
 * Class StatisticsTable
 *
 * @package SmartCAT\WP\Admin\Tables
 */
class StatisticsTable extends TableAbstract {
	/**
	 * StatisticsTable constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'statistic',
				'plural'   => 'statistics',
				'ajax'     => false,
			]
		);

		$this->bulk_action_handler();
	}

	/**
	 * Prepare items for display
	 */
	public function prepare_items() {
		$statistic_repository = self::get_repository();
		$per_page             = get_user_meta( get_current_user_id(), 'sc_dashboard_per_page', true ) ?: 20;

		$this->set_pagination_args(
			[
				'total_items' => $statistic_repository->get_count(),
				'per_page'    => intval( $per_page ),
			]
		);

		$cur_page = (int) $this->get_pagenum();
		$items    = $statistic_repository->get_all(
			intval( $per_page ) * ( $cur_page - 1 ),
			intval( $per_page )
		);

		$this->set_data( $items );

		parent::prepare_items();
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'cb'                 => '<input type="checkbox" />',
			'title'              => __( 'Title', 'translation-connectors' ),
			'sourceLang'         => __( 'Source language', 'translation-connectors' ),
			'targetLang'         => __( 'Target language', 'translation-connectors' ),
			'wordsCount'         => __( 'Words count', 'translation-connectors' ),
			'progress'           => __( 'Progress', 'translation-connectors' ),
			'status'             => __( 'Status', 'translation-connectors' ),
			'actions'            => __( 'Additional actions', 'translation-connectors' ),
		];

		return $columns;
	}

	/**
	 * @param Statistics $item
	 * @param string $column_name
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function column_default( $item, $column_name ) {
		$container = Connector::get_container();

		/** @var Utils $utils */
		$utils = $container->get( 'utils' );

		/** @var LanguageConverter $language_converter */
		$language_converter = $container->get( 'language.converter' );

		switch ( $column_name ) {
			case 'title':
				$title = get_the_title( $item->get_post_id() );

				$is_post_deleted = ! $title || empty( $title );

				$post_id = $item->get_post_id();
				$url     = $utils->get_url_to_post_by_post_id( $post_id );
				$title   = $is_post_deleted
					? '--'
					: "<a href='{$url}' target='_blank'>{$title}</a>";

				return $title;
			case 'sourceLang':
				return $language_converter->get_sc_code_by_wp( $item->get_source_language() )->get_wp_name();
			case 'targetLang':
				return $language_converter->get_sc_code_by_wp( $item->get_target_language() )->get_wp_name();
			case 'wordsCount':
				return ( ! empty( $item->get_words_count() ) ) ? $item->get_words_count() : '-';
			case 'progress':
				return $item->get_progress();
			case 'status':
				$statuses = Statistics::get_all_statuses();

				if ( isset( $statuses[ $item->get_status() ] ) ) {
					return $statuses[ $item->get_status() ];
				} else {
					return ucfirst( $item->get_status() );
				}
			case 'actions':
				return $this->get_additional_actions( $item );
			default:
				return null;
		}
	}

	/**
	 * @param Statistics $item
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function get_additional_actions( $item ) {
		$container = Connector::get_container();

		/** @var Utils $utils */
		$utils = $container->get( 'utils' );

		$message = '';
		$status  = $item->get_status();

		if ( ! empty( $item->get_target_post_id() ) ) {
			$url      = $utils->get_url_to_post_by_post_id( $item->get_target_post_id() );
			$message .= "<p><a href='{$url}' target='_blank'>" . __( 'Edit target post', 'translation-connectors' ) . '</a></p>';
		}

		if ( in_array( $status, [ 'sended', 'export', 'completed' ], true ) && ! empty( $item->get_document_id() ) ) {
			$url      = $utils->get_url_to_smartcat_by_document_id( $item->get_document_id() );
			$message .= "<p><a href='{$url}' target='_blank'>" . __( 'Go to Smartcat', 'translation-connectors' ) . '</a></p>';
		}

		return $message;
	}

	/**
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = [
			'bulk-cancel-' . $this->_args['plural'] => __( 'Cancel', 'translation-connectors' ),
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

		$post           = sanitize_post( $_POST, 'db' );
		$statistic_repo = self::get_repository();

		switch ( $action ) {
			case 'bulk-delete-' . $this->_args['plural']:
				foreach ( $post[ $this->_args['plural'] ] as $statistic_id ) {
					$statistic_repo->delete_by_id( $statistic_id );
				}
				break;
			case 'bulk-cancel-' . $this->_args['plural']:
				foreach ( $post[ $this->_args['plural'] ] as $statistic_id ) {
					$statistic = $statistic_repo->get_one_by_id( $statistic_id );
					if ( ! in_array( $statistic->get_status(), [ Statistics::STATUS_COMPLETED, Statistics::STATUS_FAILED, Statistics::STATUS_CANCELLED ], true ) ) {
						$statistic->set_status( 'canceled' );
						$statistic_repo->save( $statistic );
					}
				}
				break;
		}
	}

	/**
	 * @return string
	 */
	protected function get_default_primary_column_name() {
		return 'title';
	}

	/**
	 * @param Statistics $item
	 * @param string $column_name
	 * @param string $primary
	 *
	 * @return string
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = [
			'delete' => sprintf(
				'<a href="javascript:void( 0 );" class="delete_stat_button" data-bind="%d">%s</a>',
				$item->get_id(),
				__( 'Delete', 'translation-connectors' )
			),
		];

		if ( ! in_array( $item->get_status(), [ Statistics::STATUS_COMPLETED, Statistics::STATUS_FAILED, Statistics::STATUS_CANCELLED ], true ) ) {
			$actions = array_merge(
				[
					'cancel' => sprintf(
						'<a href="javascript:void( 0 );" class="cancel_stat_button" data-bind="%d">%s</a>',
						$item->get_id(),
						__( 'Cancel', 'translation-connectors' )
					),
				],
				$actions
			);
		}

		if ( in_array( $item->get_status(), [ Statistics::STATUS_COMPLETED ], true ) ) {
			$actions = array_merge(
				[
					'check_update' => sprintf(
						'<a href="javascript:void( 0 );" class="refresh_stat_button" data-bind="%d">%s</a>',
						$item->get_id(),
						__( 'Check updates', 'translation-connectors' )
					),
				],
				$actions
			);
		}

		return $this->row_actions( $actions );
	}

	/**
	 * Get errors repository
	 *
	 * @return StatisticRepository|null
	 */
	private static function get_repository() {
		$container = Connector::get_container();

		try {
			return $container->get( 'entity.repository.statistic' );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
