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

/** Репозиторий таблицы обмена */
class ErrorRepository extends RepositoryAbstract
{
	const TABLE_NAME = 'errors';

	public function get_table_name()
	{
		return $this->prefix . self::TABLE_NAME;
	}

	/**
	 * @param int $from
	 * @param int $limit
	 *
	 * @return Error[]
	 */
	public function get_errors( $from = 0, $limit = 100 )
	{
		$wpdb = $this->get_wp_db();
		$from = intval( $from );
		$from >= 0 || $from = 0;
		$limit = intval( $limit );

		$table_name = $this->get_table_name();
		$results   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name ORDER BY date DESC LIMIT %d, %d", [ $from, $limit ] ) );

		return $this->prepare_result( $results );
	}

	/**
	 * @param Error $error
	 * @return bool|int
	 */
	public function add( Error $error )
	{
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

		//TODO: м.б. заменить на try-catch

		if ( $wpdb->insert( $table_name, $data ) ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	public function update( Error $error )
	{
		$table_name = $this->get_table_name();
		$wpdb	  = $this->get_wp_db();

		if ( ! empty( $error->get_id() ) ) {
			$data = [
				'date'		 => $error->get_date()->format( "Y-m-d H:i:s" ),
				'type'		 => $error->get_type(),
				'shortMessage' => $error->get_short_message(),
				'message'	  => $error->get_message()
			];
			//TODO: м.б. заменить на try-catch
			if ( $wpdb->update( $table_name, $data, [ 'id' => $error->get_id() ] ) ) {
				return true;
			}
		}

		return false;
	}

	protected function do_flush( array $persists )
	{
		/* @var Error[] $persists */
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

	protected function to_entity( $row )
	{
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
}
