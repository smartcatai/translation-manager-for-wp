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
 * @method Profile get_one_by_id( int $id )
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

		$text_columns = [
			'id',
			'name',
			'vendor',
			'vendor_name',
			'source_language',
			'project_id',
			'auto_send',
			'auto_update',
		];

		$array_columns = [
			'target_languages',
			'workflow_stages',
		];

		foreach ( $text_columns as $column ) {
			if ( isset( $row->{$column} ) ) {
				$result->{'set_' . $column}( $row->{$column} );
			}
		}

		foreach ( $array_columns as $column ) {
			if ( isset( $row->{$column} ) ) {
				$result->{'set_' . $column}( json_decode( $row->{$column}, true ) );
			}
		}

		return $result;
	}

	/**
	 * @param int $from
	 * @param int $limit
	 *
	 * @return Profile[]
	 */
	public function get_all( $from = 0, $limit = 100 ) {
		$wpdb  = $this->get_wp_db();
		$from  = intval( $from );
		$limit = intval( $limit );

		$from >= 0 || $from = 0;

		$table_name = $this->get_table_name();
		$results    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name LIMIT %d, %d",
				[ $from, $limit ]
			)
		);

		return $this->prepare_result( $results );
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
	public function add( $profile ) {
		$wpdb = $this->get_wp_db();

		$data = [
			'name'             => $profile->get_name(),
			'source_language'  => $profile->get_source_language(),
			'target_languages' => wp_json_encode( $profile->get_target_languages() ),
			'project_id'       => $profile->get_project_id(),
			'workflow_stages'  => wp_json_encode( $profile->get_workflow_stages() ),
			'vendor'           => $profile->get_vendor(),
			'vendor_name'      => $profile->get_vendor_name(),
			'auto_send'        => boolval( $profile->is_auto_send() ),
			'auto_update'      => boolval( $profile->is_auto_update() ),
		];

		if ( ! empty( $profile->get_id() ) ) {
			$data['id'] = $profile->get_id();
		}

		if ( $wpdb->insert( $this->get_table_name(), $data ) ) {
			$profile->set_id( $wpdb->insert_id );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * @param Profile $profile
	 *
	 * @return mixed|void
	 */
	public function save( $profile ) {
		if ( $profile->get_id() ) {
			return $this->update( $profile );
		} else {
			return $this->add( $profile );
		}
	}

	/**
	 * Update Profile object in DB
	 *
	 * @param Profile $profile Profile object.
	 * @return bool
	 */
	public function update( $profile ) {
		$wpdb = $this->get_wp_db();

		if ( ! empty( $profile->get_id() ) ) {
			$data = [
				'name'             => $profile->get_name(),
				'source_language'  => $profile->get_source_language(),
				'target_languages' => wp_json_encode( $profile->get_target_languages() ),
				'project_id'       => $profile->get_project_id(),
				'workflow_stages'  => wp_json_encode( $profile->get_workflow_stages() ),
				'vendor'           => $profile->get_vendor(),
				'vendor_name'      => $profile->get_vendor_name(),
				'auto_send'        => boolval( $profile->is_auto_send() ),
				'auto_update'      => boolval( $profile->is_auto_update() ),
			];

			if ( $wpdb->update( $this->get_table_name(), $data, [ 'id' => $profile->get_id() ] ) ) {
				return true;
			}
		}

		return false;
	}
}
