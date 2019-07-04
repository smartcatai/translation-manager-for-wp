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

namespace SmartCAT\WP\DB\Repository;

use SmartCAT\WP\DB\DbAbstract;

/**
 * Class RepositoryAbstract
 *
 * @package SmartCAT\WP\DB\Repository
 */
abstract class RepositoryAbstract extends DbAbstract implements RepositoryInterface {
	/** @var string */
	protected $prefix = '';
	/** @var array */
	private $persists = [];

	/**
	 * RepositoryAbstract constructor.
	 *
	 * @param $prefix
	 */
	public function __construct( $prefix ) {
		parent::__construct();
		$this->prefix = $this->wpdb->get_blog_prefix() . $prefix;
	}

	/**
	 * @return string|null
	 */
	public function get_count() {
		$table_name = $this->get_table_name();
		$count      = $this->get_wp_db()->get_var( "SELECT COUNT( * ) FROM $table_name" );

		return $count;
	}

	/**
	 * @param $o
	 */
	public function persist( $o ) {
		$this->persists[] = $o;
	}

	/**
	 * @param array $persists
	 *
	 * @return mixed
	 */
	abstract protected function do_flush( array $persists );

	/**
	 *
	 */
	public function flush() {
		$this->do_flush( $this->persists );
		$this->persists = [];
	}

	/**
	 * @param $row
	 *
	 * @return mixed
	 */
	protected abstract function to_entity( $row );

	/**
	 * @param $entity
	 *
	 * @return mixed
	 */
	protected abstract function save( $entity );

	/**
	 * @param $rows
	 *
	 * @return array
	 */
	protected function prepare_result( $rows ) {
		$result = [];
		foreach ( $rows as $row ) {
			$result[] = $this->to_entity( $row );
		}

		return $result;
	}

	/**
	 * @param array $criterias
	 *
	 * @return DbAbstract|null
	 */
	public function get_one_by_id( $id ) {
		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id=%d", $id ) );

		return $row ? $this->to_entity( $row ) : null;
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public function delete_by_id( $id ) {
		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();

		if ( ! empty( $id ) ) {
			if ( $wpdb->delete( $table_name, [ 'id' => $id ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $criterias
	 *
	 * @return DbAbstract[]|null
	 */
	public function get_all_by( array $criterias ) {
		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();
		$query      = "SELECT * FROM $table_name WHERE ";

		$where = $values = [];

		foreach ( $criterias as $key => $value ) {
			$where[]  = "$key=%s";
			$values[] = $value;
		}

		$results =
			$wpdb->get_results(
				$wpdb->prepare( $query . implode( " AND ", $where ),
					$values )
			);

		return $this->prepare_result( $results );
	}

	/**
	 * @param array $criterias
	 *
	 * @return DbAbstract|null
	 */
	public function get_one_by( array $criterias ) {
		$table_name = $this->get_table_name();
		$wpdb       = $this->get_wp_db();
		$query      = "SELECT * FROM $table_name WHERE ";

		$where = $values = [];

		foreach ( $criterias as $key => $value ) {
			$where[]  = "$key=%s";
			$values[] = $value;
		}

		$row = $wpdb->get_row( $wpdb->prepare( $query . implode( " AND ", $where ), $values ) );

		return $row ? $this->to_entity( $row ) : null;
	}

	/**
	 * @param int $from
	 * @param int $limit
	 *
	 * @return DbAbstract[]
	 */
	public function get_all( $from = 0, $limit = 0 ) {
		$wpdb  = $this->get_wp_db();
		$from  = intval( $from );
		$limit = intval( $limit );

		$table_name = $this->get_table_name();
		$query      = "SELECT * FROM $table_name";

		if ( $limit > 0 ) {
			$query = $wpdb->prepare(
				"SELECT * FROM $table_name LIMIT %d, %d",
				[ $from, $limit ]
			);
		}

		$results = $wpdb->get_results( $query );

		return $this->prepare_result( $results );
	}
}
