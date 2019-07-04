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
 * Class Profile
 *
 * @package SmartCAT\WP\DB\Entity
 */
class Profile {
	/**
	 * Profile primary key
	 *
	 * @var int
	 */
	private $profile_id;

	/**
	 * Profile name
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Selected vendor GUID to send in
	 *
	 * @var string
	 */
	private $vendor;

	/**
	 * Vendor name for display when connection lost
	 *
	 * @var string
	 */
	private $vendor_name;

	/**
	 * Source language translate from
	 *
	 * @var string
	 */
	private $source_language;

	/**
	 * Array of target languages
	 *
	 * @var string[]
	 */
	private $target_languages;

	/**
	 * Array of workflow stages
	 *
	 * @var string[]
	 */
	private $workflow_stages;

	/**
	 * Project GUID to store translates
	 *
	 * @var int
	 */
	private $project_id;

	/**
	 * Automatic Send on content Create
	 *
	 * @var bool
	 */
	private $auto_send;

	/**
	 * Automatic Send on content Update
	 *
	 * @var bool
	 */
	private $auto_update;

	/**
	 * Profile ID getter
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->profile_id;
	}

	/**
	 * Profile ID setter
	 *
	 * @param int $profile_id Profile primary key.
	 *
	 * @return Profile
	 */
	public function set_id( $profile_id ) {
		$this->profile_id = $profile_id;

		return $this;
	}

	/**
	 * Profile name getter
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Profile name setter
	 *
	 * @param string $name Name of profile.
	 *
	 * @return Profile
	 */
	public function set_name( $name ) {
		$this->name = $name;

		return $this;
	}

	/**
	 * Profile Vendor
	 *
	 * @return string
	 */
	public function get_vendor() {
		return $this->vendor;
	}

	/**
	 * Profile Vendor
	 *
	 * @param string $vendor Vendor GUID.
	 *
	 * @return Profile
	 */
	public function set_vendor( $vendor ) {
		$this->vendor = $vendor;

		return $this;
	}

	/**
	 * Profile Vendor Name
	 *
	 * @return string|null
	 */
	public function get_vendor_name() {
		return $this->vendor_name;
	}

	/**
	 * Profile Vendor Name
	 *
	 * @param string $vendor_name Vendor Name.
	 *
	 * @return Profile
	 */
	public function set_vendor_name( $vendor_name ) {
		$this->vendor_name = $vendor_name;

		return $this;
	}

	/**
	 * Profile Source Language getter
	 *
	 * @return string
	 */
	public function get_source_language() {
		return $this->source_language;
	}

	/**
	 * Profile Source Language setter
	 *
	 * @param string $source_language Source Language.
	 *
	 * @return Profile
	 */
	public function set_source_language( $source_language ) {
		$this->source_language = $source_language;

		return $this;
	}

	/**
	 * Profile Target Languages getter
	 *
	 * @return string[]
	 */
	public function get_target_languages() {
		return $this->target_languages;
	}

	/**
	 * Profile Target Languages setter
	 *
	 * @param string[]|string $target_languages Target Languages.
	 *
	 * @return Profile
	 */
	public function set_target_languages( $target_languages ) {
		$this->target_languages = $target_languages;

		return $this;
	}

	/**
	 * Profile Workflow Stages getter
	 *
	 * @return string[]
	 */
	public function get_workflow_stages() {
		return $this->workflow_stages;
	}

	/**
	 * Profile Workflow Stages setter
	 *
	 * @param string[]|string $workflow_stages Workflow Stages.
	 *
	 * @return Profile
	 */
	public function set_workflow_stages( $workflow_stages ) {
		$this->workflow_stages = $workflow_stages;

		return $this;
	}

	/**
	 * Profile Automatic Send on content Create getter
	 *
	 * @return bool
	 */
	public function is_auto_send() {
		return $this->auto_send;
	}

	/**
	 * Profile Automatic Send on content Create setter
	 *
	 * @param bool $auto_send Auto Send.
	 *
	 * @return Profile
	 */
	public function set_auto_send( $auto_send ) {
		$this->auto_send = $auto_send;

		return $this;
	}

	/**
	 * Profile Automatic Send on content Update getter
	 *
	 * @return bool
	 */
	public function is_auto_update() {
		return $this->auto_update;
	}

	/**
	 * Profile Automatic Send on content Update setter
	 *
	 * @param bool $auto_update Auto Update.
	 *
	 * @return Profile
	 */
	public function set_auto_update( $auto_update ) {
		$this->auto_update = $auto_update;

		return $this;
	}

	/**
	 * Profile Project ID getter
	 *
	 * @return int
	 */
	public function get_project_id() {
		return $this->project_id;
	}

	/**
	 * Profile Project ID setter
	 *
	 * @param string $project_id Project id.
	 *
	 * @return Profile
	 */
	public function set_project_id( $project_id ) {
		$this->project_id = $project_id;

		return $this;
	}
}
