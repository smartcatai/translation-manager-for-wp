<?php

namespace SmartCAT\WP\Admin;

final class AjaxResponse {
	private $response;

	public function __construct() {
		$this->response = [
			'status'  => 'error',
			'message' => '',
			'data'    => []
		];
	}

	public function get_success_response( $message, $data = [] ) {
		$this->response['status']  = 'success';
		$this->response['message'] = $message;
		$this->response['data']    = $data;

		return json_encode( $this->response );
	}

	public function get_error_response( $message, $data = [] ) {
		$this->response['status']  = 'error';
		$this->response['message'] = $message;
		$this->response['data']    = $data;

		return json_encode( $this->response );
	}

	public function send_error( $message, $data = [], $code = 400 ) {
		http_response_code( $code );
		echo $this->get_error_response( $message, $data );
		exit;
	}

	public function send_success( $message, $data = [], $code = 200 ) {
		http_response_code( $code );
		echo $this->get_success_response( $message, $data );
		exit;
	}
}