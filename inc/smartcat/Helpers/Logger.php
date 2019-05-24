<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\Helpers;

use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Error;
use SmartCAT\WP\DB\Repository\ErrorRepository;

class Logger {
    /**
     * @param string $type
     * @param string $shortMessage
     * @param string $message
     */
    private static function add_record($type, $shortMessage, $message = '') {
        $error = new Error();

        try {
            $container = Connector::get_container();

            /** @var ErrorRepository $repository */
            $repository = $container->get('entity.repository.error');
            $error->set_date(new \DateTime());
        } catch (\Throwable $e) {
            SmartCAT::debug("[error] Logger container does not exists");
            return;
        }

        SmartCAT::debug("[{$type}] {$message}");

        $error->set_type($type)
            ->set_short_message($shortMessage)
            ->set_message($message);
        $repository->add($error);
    }

    /**
     * @param string $shortMessage
     * @param string $message
     */
    public static function info($shortMessage, $message = '') {
        self::add_record('info', $shortMessage, $message);
    }

    /**
     * @param string $shortMessage
     * @param string $message
     */
    public static function warning($shortMessage, $message = '') {
        self::add_record('warning', $shortMessage, $message);
    }

    /**
     * @param string $shortMessage string
     * @param string $message
     */
    public static function error($shortMessage, $message = '') {
        self::add_record('error', $shortMessage, $message);
    }
}