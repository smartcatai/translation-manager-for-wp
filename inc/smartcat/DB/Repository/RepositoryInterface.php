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
 * Interface RepositoryInterface
 *
 * @package SmartCAT\WP\DB\Repository
 */
interface RepositoryInterface {
	/**
	 * RepositoryInterface constructor.
	 *
	 * @param string $prefix
	 */
	public function __construct( $prefix );

	/**
	 * @return mixed
	 */
	public function get_table_name();

	/**
	 * @param $o
	 *
	 * @return mixed
	 */
	public function persist( $o );

	/**
	 * @return mixed
	 */
	public function flush();

	/**
	 * @param mixed $entity
	 *
	 * @return mixed
	 */
	public function update( $entity );

	/**
	 * @param mixed $entity
	 *
	 * @return mixed
	 */
	public function add( $entity );
}
