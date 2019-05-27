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


interface RepositoryInterface {
    /**
     * RepositoryInterface constructor.
     *
     * @param string $prefix
     */
    public function __construct($prefix);

    public function get_table_name();

    public function persist($o);

    public function flush();
}