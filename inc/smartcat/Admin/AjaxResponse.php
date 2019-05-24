<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\Admin;

final class AjaxResponse
{
    private $response;

    public function __construct()
    {
        $this->response = [
            'status'  => 'error',
            'message' => '',
            'data'    => []
        ];
    }

    public function getSuccessResponse($message, $data = [])
    {
        $this->response['status']  = 'success';
        $this->response['message'] = $message;
        $this->response['data']    = $data;

        return json_encode($this->response);
    }

    public function getErrorResponse($message, $data = [])
    {
        $this->response['status']  = 'error';
        $this->response['message'] = $message;
        $this->response['data']    = $data;

        return json_encode($this->response);
    }

    public function sendError($message, $data = [], $code = 400)
    {
        http_response_code($code);
        echo $this->getErrorResponse($message, $data);
        exit;
    }

    public function sendSuccess($message, $data = [], $code = 200)
    {
        http_response_code($code);
        echo $this->getSuccessResponse($message, $data);
        exit;
    }
}
