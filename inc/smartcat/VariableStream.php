<?php
/**
 * Created by PhpStorm.
 * User: Diversant_
 * Date: 31.07.2017
 * Time: 18:33
 */

namespace SmartCAT\WP;


class VariableStream {
	private $position;
	private $varname;

	public function stream_open( $path, $mode, $options, &$opened_path ) {
		$url            = parse_url( $path );
		$this->varname  = $url["host"];
		$this->position = 0;

		return true;
	}

	public function stream_read( $count ) {
		$p   =& $this->position;
		$ret = substr( $GLOBALS[ $this->varname ], $p, $count );
		$p   += strlen( $ret );

		return $ret;
	}

	public function stream_write( $data ) {
		$v =& $GLOBALS[ $this->varname ];
		$l = strlen( $data );
		$p =& $this->position;
		$v = substr( $v, 0, $p ) . $data . substr( $v, $p += $l );

		return $l;
	}

	public function stream_tell() {
		return $this->position;
	}

	public function stream_eof() {
		return $this->position >= strlen( $GLOBALS[ $this->varname ] );
	}

	public function stream_seek( $offset, $whence ) {
		$v = &$GLOBALS[ $this->varname ];
		$l = strlen( $v );
		$p =& $this->position;
		switch ( $whence ) {
			case SEEK_SET:
				$newPos = $offset;
				break;
			case SEEK_CUR:
				$newPos = $p + $offset;
				break;
			case SEEK_END:
				$newPos = $l + $offset;
				break;
			default:
				return false;
		}
		$ret = ( $newPos >= 0 && $newPos <= $l );
		if ( $ret ) {
			$p = $newPos;
		}

		return $ret;
	}

	public function stream_stat() {
		return [ 'size' => strlen( $GLOBALS[ $this->varname ] ) ];
	}
}