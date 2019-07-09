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

namespace SmartCAT\WP\DB\Entity;

use DateTime;

/**
 * Class Event
 *
 * @package SmartCAT\WP\DB\Entity
 */
class Event extends EntityAbstract {
	/** @var  integer */
	protected $id;
	/** @var  string */
	protected $date;
	/** @var  string */
	protected $type;
	/** @var  string */
	protected $message;

	/**
	 * @return array
	 */
	public function attributes(): array {
		return [
			'id'      => 'id',
			'date'    => 'date',
			'type'    => 'type',
			'message' => 'message',
		];
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return intval( $this->id );
	}

	/**
	 * @param int $id
	 *
	 * @return Event
	 */
	public function set_id( $id ) {
		$this->id = intval( $id );

		return $this;
	}

	/**
	 * @return DateTime
	 */
	public function get_date() {
		return DateTime::createFromFormat( 'Y-m-d H:i:s', $this->date );
	}

	/**
	 * @param DateTime $date
	 *
	 * @return Event
	 */
	public function set_date( $date ) {
		$this->date = $date->format( 'Y-m-d H:i:s' );

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * @param string $type
	 *
	 * @return Event
	 */
	public function set_type( $type ) {
		$this->type = $type;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_message() {
		return $this->message;
	}

	/**
	 * @param string $message
	 *
	 * @return Event
	 */
	public function set_message( $message ) {
		$this->message = $message;

		return $this;
	}
}
