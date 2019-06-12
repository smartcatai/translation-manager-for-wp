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

namespace SmartCAT\WP\Admin;

/**
 * Class AjaxResponse
 *
 * @package SmartCAT\WP\Admin
 */
final class AjaxResponse {
	/**
	 * Response array
	 *
	 * @var array
	 */
	private $response;

	/**
	 * AjaxResponse constructor.
	 */
	public function __construct() {
		$this->response = [
			'status'  => 'error',
			'message' => '',
			'data'    => [],
		];
	}

	/**
	 * Get success response JSON string
	 *
	 * @param string $message Answer message.
	 * @param array  $data Data to send in answer.
	 *
	 * @return false|string
	 */
	public function get_success_response( $message, $data = [] ) {
		$this->response['status']  = 'success';
		$this->response['message'] = $message;
		$this->response['data']    = $data;

		return wp_json_encode( $this->response );
	}

	/**
	 * Get error response JSON string
	 *
	 * @param string $message Answer message.
	 * @param array  $data Data to send in answer.
	 *
	 * @return false|string
	 */
	public function get_error_response( $message, $data = [] ) {
		$this->response['status']  = 'error';
		$this->response['message'] = $message;
		$this->response['data']    = $data;

		return wp_json_encode( $this->response );
	}

	/**
	 * Send error answer to WP
	 *
	 * @param string $message Answer message.
	 * @param array  $data Data to send in answer.
	 * @param int    $code Answer status code.
	 */
	public function send_error( $message, $data = [], $code = 400 ) {
		http_response_code( $code );
		echo $this->get_error_response( $message, $data );
		exit;
	}

	/**
	 * Send success answer to WP
	 *
	 * @param string $message Answer message.
	 * @param array  $data Data to send in answer.
	 * @param int    $code Answer status code.
	 */
	public function send_success( $message, $data = [], $code = 200 ) {
		http_response_code( $code );
		echo $this->get_success_response( $message, $data );
		exit;
	}
}
