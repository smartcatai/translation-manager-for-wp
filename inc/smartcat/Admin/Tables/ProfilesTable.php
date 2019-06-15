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
use SmartCAT\WP\DB\Entity\Profile;
use SmartCAT\WP\DB\Repository\ProfileRepository;
use SmartCAT\WP\Helpers\Language\LanguageConverter;

/**
 * Class ProfilesTable
 *
 * @package SmartCAT\WP\Admin\Tables
 */
class ProfilesTable extends TableAbstract {
	/**
	 * ProfilesTable constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'profile',
				'plural'   => 'profiles',
				'ajax'     => false,
			]
		);

		$this->bulk_action_handler();
	}

	/**
	 * Prepare items for display
	 */
	public function prepare_items() {
		$profiles_repository = self::get_repository();
		$per_page            = get_user_meta( get_current_user_id(), 'sc_profiles_per_page', true ) ?: 20;

		$this->set_pagination_args(
			[
				'total_items' => $profiles_repository->get_count(),
				'per_page'    => intval( $per_page ),
			]
		);

		$cur_page = (int) $this->get_pagenum();
		$items    = $profiles_repository->get_all(
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
			'cb'               => '<input type="checkbox" />',
			'name'             => __( 'Name', 'translation-connectors' ),
			'source_language'  => __( 'Source language', 'translation-connectors' ),
			'target_languages' => __( 'Target languages', 'translation-connectors' ),
			'vendor_name'      => __( 'Vendor', 'translation-connectors' ),
			'workflow_stages'  => __( 'Workflow stages', 'translation-connectors' ),
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
	 */
	public function column_default( $item, $column_name ) {
		$language_converter = self::get_language_converter();

		switch ( $column_name ) {
			case 'name':
				return $item->get_name();
			case 'source_language':
				return $language_converter->get_sc_code_by_wp( $item->get_source_language() )->get_wp_name();
			case 'target_languages':
				$languages = [];
				foreach ( $item->get_target_languages() as $wp_locale ) {
					$languages[] = $language_converter->get_sc_code_by_wp( $wp_locale )->get_wp_name();
				}
				return implode( ', ', $languages );
			case 'vendor_name':
				return $item->get_vendor_name();
			case 'workflow_stages':
				return implode( ', ', $item->get_workflow_stages() );
			case 'auto_send':
				return $item->is_auto_send() ? 'Yes' : 'No';
			case 'auto_update':
				return $item->is_auto_update() ? 'Yes' : 'No';
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

		$post = sanitize_post( $_POST );

		switch ( $action ) {
			case 'bulk-delete-' . $this->_args['plural']:
				$profile_repo = self::get_repository();
				foreach ( $post[ $this->_args['plural'] ] as $profile_id ) {
					$profile_repo->delete_by_id( $profile_id );
				}
				break;
		}
	}

	/**
	 * @return string
	 */
	protected function get_default_primary_column_name() {
		return 'name';
	}

	/**
	 * @param Profile $item
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
			'edit'   => sprintf(
				'<a href="admin.php?page=sc-edit-profile%s">%s</a>',
				'&profile=' . $item->get_id(),
				__( 'Edit', 'translation-connectors' )
			),
			'delete' => sprintf( '<a href="%s">%s</a>', '#', __( 'Delete', 'translation-connectors' ) ),
		];

		return $this->row_actions( $actions );
	}

	/**
	 * Get errors repository
	 *
	 * @return ProfileRepository|null
	 */
	private static function get_repository() {
		$container = Connector::get_container();

		try {
			return $container->get( 'entity.repository.profile' );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Get errors repository
	 *
	 * @return LanguageConverter|null
	 */
	private static function get_language_converter() {
		$container = Connector::get_container();

		try {
			return $container->get( 'language.converter' );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
