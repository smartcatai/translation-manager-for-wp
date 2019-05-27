<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\DB\Repository;

use SmartCAT\WP\DB\DbAbstract;

abstract class RepositoryAbstract extends DbAbstract implements RepositoryInterface
{
    protected $prefix = '';

    public function __construct($prefix)
    {
        parent::__construct();
        $this->prefix = $this->wpdb->get_blog_prefix() . $prefix;
    }

    public function get_count()
    {
        $table_name = $this->get_table_name();
        $count      = $this->get_wp_db()->get_var("SELECT COUNT(*) FROM $table_name");

        return $count;
    }

    private $persists = [];

    public function persist($o)
    {
        $this->persists[] = $o;
    }

    protected abstract function do_flush(array $persists);

    public function flush()
    {
        $this->do_flush($this->persists);
        $this->persists = [];
    }

    protected abstract function to_entity($row);

    protected function prepare_result($rows)
    {
        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->to_entity($row);
        }

        return $result;
    }
}
