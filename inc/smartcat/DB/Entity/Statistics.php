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

/**
 * Class Statistics
 *
 * @package SmartCAT\WP\DB\Entity
 */
class Statistics {
	const STATUS_NEW       = 'new';
	const STATUS_FAILED    = 'failed';
	const STATUS_SENDED    = 'sended';
	const STATUS_EXPORT    = 'export';
	const STATUS_COMPLETED = 'completed';
	const STATUS_CANCELED  = 'canceled';

	/** @var  integer */
	private $id;
	/** @var  integer */
	private $task_id;
	/** @var  integer */
	private $post_id;
	/** @var  string */
	private $source_language;
	/** @var  string */
	private $target_language;
	/** @var  float */
	private $progress;
	/** @var  integer */
	private $target_post_id;
	/** @var  string */
	private $document_id;
	/** @var  string */
	private $status;
	/** @var  integer */
	private $error_count = 0;


	/**
	 * @return array
	 */
	public static function get_all_statuses() {
		return [
			self::STATUS_NEW       => __( 'Submitted', 'translation-connectors' ),
			self::STATUS_FAILED    => __( 'Failed', 'translation-connectors' ),
			self::STATUS_SENDED    => __( 'In Progress', 'translation-connectors' ),
			self::STATUS_EXPORT    => __( 'In Progress', 'translation-connectors' ),
			self::STATUS_COMPLETED => __( 'Completed', 'translation-connectors' ),
			self::STATUS_CANCELED  => __( 'Canceled', 'translation-connectors' ),
		];
	}
	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param int $id
	 *
	 * @return Statistics
	 */
	public function set_id( $id ) {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_task_id() {
		return $this->task_id;
	}

	/**
	 * @param int $task_id
	 *
	 * @return Statistics
	 */
	public function set_task_id( $task_id ) {
		$this->task_id = $task_id;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_post_id() {
		return $this->post_id;
	}

	/**
	 * @param int $post_id
	 *
	 * @return Statistics
	 */
	public function set_post_id( $post_id ) {
		$this->post_id = $post_id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_source_language() {
		return $this->source_language;
	}

	/**
	 * @param string $source_language
	 *
	 * @return Statistics
	 */
	public function set_source_language( $source_language ) {
		$this->source_language = $source_language;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_target_language() {
		return $this->target_language;
	}

	/**
	 * @param string $target_language
	 *
	 * @return Statistics
	 */
	public function set_target_language( $target_language ) {
		$this->target_language = $target_language;

		return $this;
	}

	/**
	 * @return float
	 */
	public function get_progress() {
		return $this->progress;
	}

	/**
	 * @param float $progress
	 *
	 * @return Statistics
	 */
	public function set_progress( $progress ) {
		$this->progress = $progress;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_target_post_id() {
		return $this->target_post_id;
	}

	/**
	 * @param int $target_post_id
	 *
	 * @return Statistics
	 */
	public function set_target_post_id( $target_post_id ) {
		$this->target_post_id = $target_post_id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_document_id() {
		return $this->document_id;
	}

	/**
	 * @param string $document_id
	 *
	 * @return Statistics
	 */
	public function set_document_id( $document_id ) {
		$this->document_id = $document_id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * @param string $status
	 *
	 * @return Statistics
	 */
	public function set_status( $status ) {
		$this->status = $status;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_error_count() {
		return $this->error_count;
	}

	/**
	 * @param int $error_count
	 *
	 * @return Statistics
	 */
	public function set_error_count( $error_count ) {
		$this->error_count = $error_count;

		return $this;
	}

	/**
	 * @param int $inc
	 *
	 * @return Statistics
	 */
	public function inc_error_count( $inc = 1 ) {
		$this->set_error_count( $this->get_error_count() + $inc );

		return $this;
	}
}
