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

namespace SmartCAT\WP;

/**
 * Class VariableStream
 *
 * @package SmartCAT\WP
 */
class VariableStream {
	/**
	 * @var
	 */
	private $position;
	/**
	 * @var
	 */
	private $varname;

	/**
	 * @param $path
	 * @param $mode
	 * @param $options
	 * @param $opened_path
	 *
	 * @return bool
	 */
	public function stream_open( $path, $mode, $options, &$opened_path ) {
		$url            = wp_parse_url( $path );
		$this->varname  = $url['host'];
		$this->position = 0;

		return true;
	}

	/**
	 * @param $count
	 *
	 * @return bool|string
	 */
	public function stream_read( $count ) {
		$p   =& $this->position;
		$ret = substr( $GLOBALS[ $this->varname ], $p, $count );
		$p  += strlen( $ret );

		return $ret;
	}

	/**
	 * @param $data
	 *
	 * @return int
	 */
	public function stream_write( $data ) {
		$v =& $GLOBALS[ $this->varname ];
		$l = strlen( $data );
		$p =& $this->position;
		$v = substr( $v, 0, $p ) . $data . substr( $v, $p += $l );

		return $l;
	}

	/**
	 * @return mixed
	 */
	public function stream_tell() {
		return $this->position;
	}

	/**
	 * @return bool
	 */
	public function stream_eof() {
		return $this->position >= strlen( $GLOBALS[ $this->varname ] );
	}

	/**
	 * @param $offset
	 * @param $whence
	 *
	 * @return bool
	 */
	public function stream_seek( $offset, $whence ) {
		$v = &$GLOBALS[ $this->varname ];
		$l = strlen( $v );
		$p =& $this->position;
		switch ( $whence ) {
			case SEEK_SET:
				$new_pos = $offset;
				break;
			case SEEK_CUR:
				$new_pos = $p + $offset;
				break;
			case SEEK_END:
				$new_pos = $l + $offset;
				break;
			default:
				return false;
		}
		$ret = ( $new_pos >= 0 && $new_pos <= $l );
		if ( $ret ) {
			$p = $new_pos;
		}

		return $ret;
	}

	/**
	 * @return array
	 */
	public function stream_stat() {
		return [ 'size' => strlen( $GLOBALS[ $this->varname ] ) ];
	}
}
