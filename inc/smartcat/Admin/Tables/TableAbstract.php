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

use SmartCAT\WP\Admin\Statistics;

/**
 * Class TableAbstract
 *
 * @package SmartCAT\WP\Admin\Tables
 */
abstract class TableAbstract extends \WP_List_Table {
	/**
	 * Data for render
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Get HTML code to render
	 *
	 * @return string
	 */
	public function display() {
		ob_start();
		$this->prepare_items();
		parent::display();

		return ob_get_clean();
	}

	/**
	 * Prepare items for display
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = [];
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$this->items           = $this->get_data();
	}

	/**
	 * Getter for table data
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Setter for table data
	 *
	 * @param Statistics[] $data Statistic data to set in.
	 * @return $this
	 */
	public function set_data( $data ) {
		$this->data = $data;

		return $this;
	}
}
