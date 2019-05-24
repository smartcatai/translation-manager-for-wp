<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\Helpers\Language;

//фактически выходит - relations
final class LanguageEntity {
    private $wp_name;
    private $sc_name;
    private $wp_code;
    private $sc_code;

    /**
     * @return mixed
     */
    public function get_wp_name() {
        return $this->wp_name;
    }

    /**
     * @param mixed $wp_name
     *
     * @return LanguageEntity
     */
    public function set_wp_name($wp_name) {
        $this->wp_name = $wp_name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function get_sc_name() {
        return $this->sc_name;
    }

    /**
     * @param mixed $sc_name
     *
     * @return LanguageEntity
     */
    public function set_sc_name($sc_name) {
        $this->sc_name = $sc_name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function get_wp_code() {
        return $this->wp_code;
    }

    /**
     * @param mixed $wp_code
     *
     * @return LanguageEntity
     */
    public function set_wp_code($wp_code) {
        $this->wp_code = $wp_code;

        return $this;
    }

    /**
     * @return mixed
     */
    public function get_sc_code() {
        return $this->sc_code;
    }

    /**
     * @param mixed $sc_code
     *
     * @return LanguageEntity
     */
    public function set_sc_code($sc_code) {
        $this->sc_code = $sc_code;

        return $this;
    }

    public function __construct($wp_code, $sc_code, $wp_name, $sc_name = null) {
        $this->set_sc_code($sc_code);
        $this->set_wp_code($wp_code);
        $this->set_wp_name($wp_name);
        $this->set_sc_name(! is_null($sc_name) ? $sc_name : $wp_name);
    }
}