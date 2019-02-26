<?php

namespace SmartCAT\WP\Admin;

use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\Helpers\Utils;

class StatisticsTable extends \WP_List_Table {
	protected $data = [];

	public function get_columns() {
		$columns = [
			'title'      => __( 'Title', 'translation-connectors' ),
			'sourceLang' => __( 'Source language', 'translation-connectors' ),
			'targetLang' => __( 'Target language', 'translation-connectors' ),
			'wordsCount' => __( 'Words count', 'translation-connectors' ),
			//'progress'   => __( 'Progress', 'translation-connectors' ),
			'status'     => __( 'Status', 'translation-connectors' ),
			'smartcat_project'     => __( 'Smartcat project', 'translation-connectors' ),
			'editPost'   => __( 'Edit post', 'translation-connectors' ),
			'refresh'    => __( 'Update translation', 'translation-connectors' ),
		];

		return $columns;
	}

	public function display() {
		$this->prepare_items();
		parent::display();
	}

	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = [];
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$this->items           = $this->get_data();
	}

	public function get_data() {
		return $this->data;
	}

	public function set_data( $data ) {
		$this->data = $data;

		return $this;
	}


	/**
	 * @param Statistics $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {

		$container = Connector::get_container();
		/** @var Utils $utils */
		$utils = $container->get( 'utils' );

		switch ( $column_name ) {
			case 'title':
				$title = get_the_title( $item->get_post_id() );
				$is_post_deleted = !$title || empty( $title );
				if ($is_post_deleted) {
					$title = '-';
				}

				$post_id = $item->get_post_id();
				$url     = $utils->get_url_to_post_by_post_id( $post_id );
				$title   = $is_post_deleted
					? $title
					: "<a href='{$url}' target='_blank'>{$title}</a>";

				return $title;
			case 'sourceLang':
				$languages = $utils->get_pll_languages();

				$source_language = isset( $languages[ $item->get_source_language() ] )
					? $languages[ $item->get_source_language() ]
					: $item->get_source_language();

				return $source_language;
			case 'targetLang':
				$languages = $utils->get_pll_languages();

				$target_language = isset( $languages[ $item->get_target_language() ] )
					? $languages[ $item->get_target_language() ]
					: $item->get_target_language();

				return $target_language;
			case 'wordsCount':
				$words_count = ( ! empty( $item->get_words_count() ) ) ? $item->get_words_count() : '-';

				return $words_count;
			/*case 'progress':
				return $item->getProgress();*/
			case 'smartcat_project':
				$status = $item->get_status();

				$message = __('Go to Smartcat', 'translation-connectors');

				if (in_array($status, ['sended', 'export', 'completed']) && ! empty( $item->get_document_id())) {
					$document_id = $item->get_document_id();
					$url         = $utils->get_url_to_smartcat_by_document_id( $document_id );
					$message     = "<a href='{$url}' target='_blank'>{$message}</a>";

					return $message;
				}

				return '-';
			case 'status':
				switch ( $item->get_status() ) {
					case 'new':
						return __( 'Submitted', 'translation-connectors' );
					case 'sended':
					case 'export':
						return __( 'In progress', 'translation-connectors' );
					case 'completed':
						return __( 'Completed', 'translation-connectors' );
					default:
						return ucfirst($item->get_status());
				}
			case 'editPost':
				$message = '-';

				if ( ! empty( $item->get_target_post_id() ) ) {
					$post_id = $item->get_target_post_id();
					$url     = $utils->get_url_to_post_by_post_id( $post_id );
					$message = "<a href='{$url}' target='_blank'>" . __( 'Edit post', 'translation-connectors' ) . "</a>";
				}

				return $message;
			case 'refresh':
				$message = '-';

				if ( ! empty( $item->get_target_post_id() && $item->get_status() == 'completed') ) {
					$message = "<a href='#'  class='refresh_stat_button' data-bind='{$item->get_id()}'>" . __( 'Check updates', 'translation-connectors' ) . "</a>";
				}

				return $message;
			default:
				return null;
				//return print_r( $item, true );
		}
	}
}