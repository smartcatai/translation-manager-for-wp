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

use SmartCAT\WP\DB\Entity\Profile;

class ProfileRepository extends RepositoryAbstract
{
	const TABLE_NAME = 'profiles';

	/**
	 * @return string
	 */
	protected function getTableName()
	{
		return $this->prefix . self::TABLE_NAME;
	}

	/**
	 * @param \stdClass $row
	 * @return Profile
	 *
	 * @SuppressWarnings( PHPMD.CyclomaticComplexity )
	 * @SuppressWarnings( PHPMD.NPathComplexity )
	 */
	protected function toEntity( $row )
	{
		$result = new Profile();

		if ( isset( $row->id ) ) {
			$result->setId( intval( $row->id ) );
		}

		if ( isset( $row->vendor ) ) {
			$result->setVendor( $row->vendor );
		}

		if ( isset( $row->vendorName ) ) {
			$result->setVendorName( $row->vendorName );
		}

		if ( isset( $row->sourceLanguage ) ) {
			$result->setSourceLanguage( $row->sourceLanguage );
		}

		if ( isset( $row->targetLanguages ) ) {
			$result->setTargetLanguages( unserialize( $row->targetLanguages ) );
		}

		if ( isset( $row->projectID ) ) {
			$result->setProjectID( intval( $row->projectID ) );
		}

		if ( isset( $row->workflowStages ) ) {
			$result->setWorkflowStages( $row->workflowStages );
		}

		if ( isset( $row->autoSend ) ) {
			$result->setAutoSend( boolval( $row->autoSend ) );
		}

		if ( isset( $row->autoUpdate ) ) {
			$result->setAutoUpdate( boolval( $row->autoUpdate ) );
		}

		return $result;
	}

	/**
	 * @param Profile[] $persists
	 */
	protected function doFlush( array $persists )
	{
		foreach ( $persists as $profile ) {
			if ( $profile instanceof Profile ) {
				if ( empty( $profile->getId() ) ) {
					if ( $res = $this->add( $profile ) ) {
						$profile->setId( $res );
					}
				} else {
					$this->update( $profile );
				}
			}
		}
	}

	/**
	 * @param Profile $profile
	 * @return bool|int
	 */
	public function add( Profile $profile )
	{
		$wpdb = $this->get_wp_db();

		$data = [
			'sourceLanguage'	=> $profile->getSourceLanguage(),
			'targetLanguages'   => serialize( $profile->getTargetLanguages() ),
			'projectID'		 => $profile->getProjectID(),
			'workflowStages'	=> serialize( $profile->getWorkflowStages() ),
			'vendor'			=> $profile->getVendor(),
			'vendorName'		=> $profile->getVendorName(),
			'autoSend'		  => $profile->isAutoSend(),
			'autoUpdate'		=> $profile->isAutoUpdate(),
		];

		if ( !empty( $profile->getId() ) ) {
			$data['id'] = $profile->getId();
		}

		if ( $wpdb->insert( $this->getTableName(), $data ) ) {
			$profile->setId( $wpdb->insert_id );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * @param Profile $profile
	 * @return bool
	 */
	public function update( Profile $profile )
	{
		$wpdb = $this->get_wp_db();

		if ( !empty( $profile->getId() ) ) {
			$data = [
				'sourceLanguage'	=> $profile->getSourceLanguage(),
				'targetLanguages'   => serialize( $profile->getTargetLanguages() ),
				'projectID'		 => $profile->getProjectID(),
				'workflowStages'	=> serialize( $profile->getWorkflowStages() ),
				'vendor'			=> $profile->getVendor(),
				'vendorName'		=> $profile->getVendorName(),
				'autoSend'		  => $profile->isAutoSend(),
				'autoUpdate'		=> $profile->isAutoUpdate(),
			];

			if ( $wpdb->update( $this->getTableName(), $data, [ 'id' => $profile->getId() ] ) ) {
				return true;
			}
		}

		return false;
	}

	/** @deprecated  */
	protected function do_flush( array $persists )
	{
		return $this->doFlush( $persists );
	}

	/** @deprecated  */
	protected function to_entity( $row )
	{
		return $this->toEntity( $row );
	}

	/** @deprecated  */
	public function get_table_name()
	{
		return $this->getTableName();
	}
}
