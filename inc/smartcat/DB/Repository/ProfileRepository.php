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

/**
 * Class ProfileRepository
 *
 * @package SmartCAT\WP\DB\Repository
 */
class ProfileRepository extends RepositoryAbstract {
	const TABLE_NAME = 'profiles';

	/**
	 * Get entity table name
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->prefix . self::TABLE_NAME;
	}

	/**
	 * Serialize RAW data from DB to Profile object
	 *
	 * @param \stdClass $row RAW data from DB in stdClass.
	 * @return Profile
	 *
	 * @SuppressWarnings( PHPMD.CyclomaticComplexity )
	 * @SuppressWarnings( PHPMD.NPathComplexity )
	 */
	protected function to_entity( $row ) {
		$result = new Profile();

		$columns = [
			'id',
			'vendor',
			'vendor_name',
			'source_language',
			'target_languages',
			'project_id',
			'workflow_stages',
			'auto_send',
			'auto_update',
		];

		foreach ( $columns as $column ) {
			if ( isset( $row->{$column} ) ) {
				$result->{'set_' . $column}( $row->{$column} );
			}
		}

		return $result;
	}

	/**
	 * Flush data
	 *
	 * @param Profile[] $persists Persists.
	 */
	protected function do_flush( array $persists ) {
		foreach ( $persists as $profile ) {
			if ( $profile instanceof Profile ) {
				if ( empty( $profile->get_id() ) ) {
					$res = $this->add( $profile );

					if ( $res ) {
						$profile->set_id( $res );
					}
				} else {
					$this->update( $profile );
				}
			}
		}
	}

	/**
	 * Add profile record to DB
	 *
	 * @param Profile $profile Profile object.
	 * @return bool|int
	 */
	public function add( Profile $profile ) {
		$wpdb = $this->get_wp_db();

		$data = [
			'source_language'  => $profile->get_source_language(),
			'target_languages' => wp_json_encode( $profile->get_target_languages() ),
			'project_id'       => $profile->get_project_id(),
			'workflow_stages'  => $profile->get_workflow_stages(),
			'vendor'           => $profile->get_vendor(),
			'vendor_name'      => $profile->get_vendor_name(),
			'auto_send'        => $profile->is_auto_send(),
			'auto_update'      => $profile->is_auto_update(),
		];

		if ( ! empty( $profile->get_id() ) ) {
			$data['id'] = $profile->get_id();
		}

		if ( $wpdb->insert( $this->getTableName(), $data ) ) {
			$profile->set_id( $wpdb->insert_id );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Update Profile object in DB
	 *
	 * @param Profile $profile Profile object.
	 * @return bool
	 */
	public function update( Profile $profile ) {
		$wpdb = $this->get_wp_db();

		if ( ! empty( $profile->get_id() ) ) {
			$data = [
				'source_language'  => $profile->get_source_language(),
				'target_languages' => wp_json_encode( $profile->get_target_languages() ),
				'project_id'       => $profile->get_project_id(),
				'workflow_stages'  => $profile->get_workflow_stages(),
				'vendor'           => $profile->get_vendor(),
				'vendor_name'      => $profile->get_vendor_name(),
				'auto_send'        => $profile->is_auto_send(),
				'auto_update'      => $profile->is_auto_update(),
			];

			if ( $wpdb->update( $this->get_table_name(), $data, [ 'id' => $profile->get_id() ] ) ) {
				return true;
			}
		}

		return false;
	}
}
