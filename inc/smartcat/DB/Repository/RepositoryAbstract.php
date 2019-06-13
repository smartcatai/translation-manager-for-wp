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
	/**
	 * @var string
	 */
	protected $prefix = '';
	/**
	 * @var array
	 */
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
		$count	    = $this->get_wp_db()->get_var( "SELECT COUNT( * ) FROM $table_name" );

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
}
