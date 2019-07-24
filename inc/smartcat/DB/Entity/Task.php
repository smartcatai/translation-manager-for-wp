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
 * Class Task
 *
 * @package SmartCAT\WP\DB\Entity
 */
class Task extends EntityAbstract {
	/** @var  integer */
	protected $id;
	/** @var  string */
	protected $source_language;
	/** @var  string */
	protected $target_languages;
	/** @var  int */
	protected $profile_id;
	/** @var  string */
	protected $project_id = null;
	/** @var  string */
	protected $workflow_stages;
	/** @var  string */
	protected $vendor_id;

	/**
	 * @return array
	 */
	public function attributes(): array {
		return [
			'id'              => 'id',
			'sourceLanguage'  => 'source_language',
			'targetLanguages' => 'target_languages',
			'projectID'       => 'project_id',
			'vendorID'        => 'vendor_id',
			'workflowStages'  => 'workflow_stages',
			'profileID'       => 'profile_id',
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
	 * @return Task
	 */
	public function set_id( $id ) {
		$this->id = intval( $id );

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
	 * @return Task
	 */
	public function set_source_language( $source_language ) {
		$this->source_language = $source_language;

		return $this;
	}

	/**
	 * @return array
	 */
	public function get_target_languages() {
		return unserialize( $this->target_languages );
	}

	/**
	 * @param string[] $target_languages
	 *
	 * @return Task
	 */
	public function set_target_languages( $target_languages ) {
		$this->target_languages = serialize( $target_languages );

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function get_project_id() {
		return $this->project_id;
	}

	/**
	 * @param string $project_id
	 *
	 * @return Task
	 */
	public function set_project_id( $project_id ) {
		$this->project_id = $project_id;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_profile_id() {
		return intval( $this->profile_id );
	}

	/**
	 * @param int $profile_id
	 *
	 * @return Task
	 */
	public function set_profile_id( $profile_id ) {
		$this->profile_id = intval( $profile_id );

		return $this;
	}

	/**
	 * @return array
	 */
	public function get_workflow_stages() {
		return json_decode( $this->workflow_stages, true );
	}

	/**
	 * @param array $workflow_stages
	 *
	 * @return Task
	 */
	public function set_workflow_stages( $workflow_stages ) {
		$this->workflow_stages = wp_json_encode( $workflow_stages );

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_vendor_id() {
		return $this->vendor_id;
	}

	/**
	 * @param string $vendor_id
	 *
	 * @return Task
	 */
	public function set_vendor_id( $vendor_id ) {
		$this->vendor_id = $vendor_id;

		return $this;
	}
}
