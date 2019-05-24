<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\DB\Entity;

use DateTime;

class Error
{
    /** @var  integer */
    private $id;

    /** @var  DateTime */
    private $date;

    /** @var  string */
    private $type;

    /** @var  string */
    private $short_message;

    /** @var  string */
    private $message;

    /**
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Error
     */
    public function set_id($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function get_date()
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     *
     * @return Error
     */
    public function set_date(DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Error
     */
    public function set_type($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function get_short_message()
    {
        return $this->short_message;
    }

    /**
     * @param string $short_message
     *
     * @return Error
     */
    public function set_short_message($short_message)
    {
        $this->short_message = $short_message;

        return $this;
    }

    /**
     * @return string
     */
    public function get_message()
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return Error
     */
    public function set_message($message)
    {
        $this->message = $message;

        return $this;
    }
}
