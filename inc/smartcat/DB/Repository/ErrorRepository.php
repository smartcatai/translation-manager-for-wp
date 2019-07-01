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

use SmartCAT\WP\DB\Entity\Error;

/**
 * Class ErrorRepository
 *
 * @method Error get_one_by_id( int $id )
 * @method Error[] get_all_by( array $criterias )
 * @method Error get_one_by( array $criterias )
 * @package SmartCAT\WP\DB\Repository
 */
class ErrorRepository extends RepositoryAbstract {
	const TABLE_NAME = 'errors';

	/**
	 * @return string
	 */
	public function get_table_name() {
		return $this->prefix . self::TABLE_NAME;
	}

	/**
	 * @param int $from
	 * @param int $limit
	 *
	 * @return Error[]
	 */
	public function get_all( $from = 0, $limit = 0 ) {
		$wpdb  = $this->get_wp_db();
		$from  = intval( $from );
		$limit = intval( $limit );

		$table_name = $this->get_table_name();
		$query      = "SELECT * FROM $table_name";

		if ( $limit > 0 ) {
			$query = $wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY date DESC LIMIT %d, %d",
				[ $from, $limit ]
			);
		}

		$results = $wpdb->get_results( $query );

		return $this->prepare_result( $results );
	}

	/**
	 * @param Error $error
	 * @return bool|int
	 */
	public function add( $error ) {
		$table_name = $this->get_table_name();
		$wpdb	  = $this->get_wp_db();

		$data = [
			'date'		 => $error->get_date()->format( "Y-m-d H:i:s" ),
			'type'		 => $error->get_type(),
			'shortMessage' => $error->get_short_message(),
			'message'	  => $error->get_message()
		];

		if ( ! empty( $error->get_id() ) ) {
			$data['id'] = $error->get_id();
		}

		if ( $wpdb->insert( $table_name, $data ) ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * @param $error
	 *
	 * @return bool|mixed
	 */
	public function update( $error ) {
		$table_name = $this->get_table_name();
		$wpdb	  = $this->get_wp_db();

		if ( ! empty( $error->get_id() ) ) {
			$data = [
				'date'		 => $error->get_date()->format( "Y-m-d H:i:s" ),
				'type'		 => $error->get_type(),
				'shortMessage' => $error->get_short_message(),
				'message'	  => $error->get_message()
			];

			if ( $wpdb->update( $table_name, $data, [ 'id' => $error->get_id() ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Error[] $persists
	 *
	 * @return mixed|void
	 */
	protected function do_flush( array $persists ) {
		foreach ( $persists as $error ) {
			if ( get_class( $error ) === 'SmartCAT\WP\DB\Entity\Error' ) {
				if ( empty( $error->get_id() ) ) {
					if ( $res = $this->add( $error ) ) {
						$error->set_id( $res );
					}
				} else {
					$this->update( $error );
				}
			}
		}
	}

	/**
	 * @param $row
	 *
	 * @return mixed|Error
	 */
	protected function to_entity( $row ) {
		$result = new Error();

		if ( isset( $row->id ) ) {
			$result->set_id( intval( $row->id ) );
		}

		if ( isset( $row->date ) ) {
			$result->set_date( \DateTime::createFromFormat( "Y-m-d H:i:s", $row->date ) );
		}

		if ( isset( $row->type ) ) {
			$result->set_type( $row->type );
		}

		if ( isset( $row->shortMessage ) ) {
			$result->set_short_message( $row->shortMessage );
		}

		if ( isset( $row->message ) ) {
			$result->set_message( $row->message );
		}

		return $result;
	}

	/**
	 * @param Error $error
	 *
	 * @return mixed|void
	 */
	public function save( $error ) {
		if ( $error->get_id() ) {
			return $this->update( $error );
		} else {
			return $this->add( $error );
		}
	}
}
