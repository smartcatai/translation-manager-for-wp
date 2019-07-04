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
class Task {
	/** @var  integer */
	private $id;

	/** @var  string */
	private $source_language;

	/** @var  string[] */
	private $target_languages;

	/** @var int */
	private $profile_id;

	/** @var  string */
	private $project_id = null;

    /** @var  array */
	private $workflow_stages;

    /** @var  string */
    private $vendor_id;

    /**
     * @return array
     */
    public function get_workflow_stages()
    {
        return $this->workflow_stages;
    }

    /**
     * @param array $workflow_stages
     * @return Task
     */
    public function set_workflow_stages($workflow_stages)
    {
        $this->workflow_stages = $workflow_stages;

        return $this;
    }

    /**
     * @return string
     */
    public function get_vendor_id()
    {
        return $this->vendor_id;
    }

    /**
     * @param string $vendor_id
     * @return Task
     */
    public function set_vendor_id($vendor_id)
    {
        $this->vendor_id = $vendor_id;

        return $this;
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
	 * @return Task
	 */
	public function set_id( $id ) {
		$this->id = $id;

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
	 * @return string[]
	 */
	public function get_target_languages() {
		return $this->target_languages;
	}

	/**
	 * @param string[] $target_languages
	 *
	 * @return Task
	 */
	public function set_target_languages( $target_languages ) {
		$this->target_languages = $target_languages;

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
	 * @return string|null
	 */
	public function get_profile_id() {
		return $this->profile_id;
	}

	/**
	 * @param int $profile_id
	 *
	 * @return Task
	 */
	public function set_profile_id( $profile_id ) {
		$this->profile_id = $profile_id;

		return $this;
	}
}
