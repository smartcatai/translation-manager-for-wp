<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 16.06.2017
 * Time: 19:20
 */

namespace SmartCAT\WP\DB\Repository;


abstract class RepositoryAbstract implements RepositoryInterface {

	protected $prefix = '';
	private $wpdb;

	public function __construct( $prefix ) {
		global $wpdb;
		$this->prefix = $wpdb->get_blog_prefix() . $prefix;
		$this->wpdb   = $wpdb;
	}

	public function get_wp_db() {
		return $this->wpdb;
	}

	protected function create_table( $sql ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$charset_collate = "DEFAULT CHARACTER SET {$this->get_wp_db()->charset} COLLATE {$this->get_wp_db()->collate}";
		$sql             = "$sql{$charset_collate};";
		dbDelta( $sql );
	}

	protected function drop_table( $tableName ) {
		$this->get_wp_db()->query( "DROP TABLE IF EXISTS $tableName" );
	}

	public function get_count() {
		$table_name = $this->get_table_name();
		$count      = $this->get_wp_db()->get_var( "SELECT COUNT(*) FROM $table_name" );

		return $count;
	}

	private $persists = [];

	public function persist( $o ) {
		$this->persists[] = $o;
	}

	protected abstract function do_flush(array $persists);

	public function flush() {
		$this->do_flush($this->persists);
		$this->persists = [];
	}

	protected abstract function to_entity( $row );

	protected function prepare_result( $rows ) {
		$result = [];
		foreach ( $rows as $row ) {
			$result[] = $this->to_entity( $row );
		}

		return $result;
	}

}