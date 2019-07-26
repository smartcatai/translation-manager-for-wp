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
class Statistics extends EntityAbstract {
	/** @var  integer */
	protected $id;
	/** @var  integer */
	protected $task_id;
	/** @var  integer */
	protected $post_id;
	/** @var  string */
	protected $source_language;
	/** @var  string */
	protected $target_language;
	/** @var  float */
	protected $progress;
	/** @var  integer */
	protected $target_post_id;
	/** @var  string */
	protected $document_id;
	/** @var  string */
	protected $status;
	/** @var  integer */
	protected $error_count = 0;

	const STATUS_NEW       = 'new';
	const STATUS_FAILED    = 'failed';
	const STATUS_SENDED    = 'sended';
	const STATUS_EXPORT    = 'export';
	const STATUS_COMPLETED = 'completed';
	const STATUS_CANCELED  = 'canceled';

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
	 * @return array
	 */
	public function attributes(): array {
		return [
			'id'             => 'id',
			'taskId'         => 'task_id',
			'postID'         => 'post_id',
			'sourceLanguage' => 'source_language',
			'targetLanguage' => 'target_language',
			'progress'       => 'progress',
			'targetPostID'   => 'target_post_id',
			'documentID'     => 'document_id',
			'status'         => 'status',
			'errorCount'     => 'error_count',
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
	 * @return Statistics
	 */
	public function set_id( $id ) {
		$this->id = intval( $id );

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_task_id() {
		return $this->task_id;
	}

	/**
	 * @param string $task_id
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
		return intval( $this->post_id );
	}

	/**
	 * @param int $post_id
	 *
	 * @return Statistics
	 */
	public function set_post_id( $post_id ) {
		$this->post_id = intval( $post_id );

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
		return floatval( $this->progress );
	}

	/**
	 * @param float $progress
	 *
	 * @return Statistics
	 */
	public function set_progress( $progress ) {
		$this->progress = floatval( $progress );

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_target_post_id() {
		return intval( $this->target_post_id );
	}

	/**
	 * @param int $target_post_id
	 *
	 * @return Statistics
	 */
	public function set_target_post_id( $target_post_id ) {
		$this->target_post_id = intval( $target_post_id );

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
		return intval( $this->error_count );
	}

	/**
	 * @param int $error_count
	 *
	 * @return Statistics
	 */
	public function set_error_count( $error_count ) {
		$this->error_count = intval( $error_count );

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
