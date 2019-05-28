<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\DB;

abstract class DbAbstract
{
    protected $wpdb;

    /**
     * DbAbstract constructor.
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * @return \wpdb
     */
    public function get_wp_db()
    {
        return $this->wpdb;
    }

    /**
     * @param $sql
     */
    protected function create_table($sql)
    {
        $charset_collate = "DEFAULT CHARACTER SET {$this->get_wp_db()->charset} COLLATE {$this->get_wp_db()->collate}";
        $sql = "$sql{$charset_collate};";
        $this->update_table($sql);
    }

    /**
     * @param $tableName
     */
    protected function drop_table($tableName)
    {
        $this->get_wp_db()->query("DROP TABLE IF EXISTS $tableName");
    }

    /**
     * @return int
     */
    protected function get_plugin_version()
    {
        if (defined('SMARTCAT_PLUGIN_FILE')) {
            $plugin_data = get_file_data(SMARTCAT_PLUGIN_FILE, [ 'Version' => 'Version' ]);
            return $plugin_data['Version'];
        }
        return 0;
    }

    /**
     * @param $sql
     */
    protected function update_table($sql)
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
