<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\WP;

//TODO: попробовать заюзать класс в сценарии сохранения настроек в связи с 301 редиректом при успехе
class Notice implements InitInterface
{

    public function plugin_init() {
        if (! session_id()) {
            session_start();
        }
        if (! isset($_SESSION['smartcat_connection_notices'])) {
            $_SESSION['smartcat_connection_notices'] = [];
        }
        add_action('admin_notices', [ $this, 'show_notice' ], 999);
    }

    public function show_notice() {
        $notices = $_SESSION['smartcat_connection_notices'];
        foreach ($notices as $notice) {
            //updated notice is-dismissible
            echo '<div id="message" class="' . $notice['class'] . '"><p>' . $notice['message'] . '</p></div>';
        }
        $_SESSION['smartcat_connection_notices'] = [];
    }

    /**
     * Добавить обычное уведомление
     *
     * @param string $message - сообщение
     * @param bool $is_dismissible - Закрываемое уведомление?
     */
    public function add_notice($message, $is_dismissible = true) {
        $_SESSION['smartcat_connection_notices'][] = [
            'class'   => 'notice' . ($is_dismissible ? ' is-dismissible' : ''),
            'message' => $message
        ];
    }

    /**
     * Добавить уведомление об успешной операции
     *
     * @param string $message - сообщение
     * @param bool $is_dismissible - Закрываемое уведомление?
     */
    public function add_success($message, $is_dismissible = true) {
        $_SESSION['smartcat_connection_notices'][] = [
            'class'   => 'updated' . ($is_dismissible ? ' notice is-dismissible' : ''),
            'message' => $message
        ];
    }

    /**
     * Добавить уведомление-предупрежение
     *
     * @param string $message - сообщение
     * @param bool $is_dismissible - Закрываемое уведомление?
     */
    public function add_warning($message, $is_dismissible = true) {
        $_SESSION['smartcat_connection_notices'][] = [
            'class'   => 'notice notice-warning' . ($is_dismissible ? ' is-dismissible' : ''),
            'message' => $message
        ];
    }

    /**
     * Добавить уведомление об ошибки
     *
     * @param string $message - сообщение
     * @param bool $is_dismissible - Закрываемое уведомление?
     */
    public function add_error($message, $is_dismissible = true) {
        $_SESSION['smartcat_connection_notices'][] = [
            'class'   => 'error' . ($is_dismissible ? ' notice is-dismissible' : ''),
            'message' => $message
        ];
    }
}
