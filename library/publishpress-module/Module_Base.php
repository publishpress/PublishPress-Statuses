<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2023 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace PublishPress;

if (!class_exists('PublishPress\PPP_Module_Base')) {
    /**
     * PublishPress_Module
     */
    class PPP_Module_Base 
    {
        public $title = '';
        public $short_description = '';

        public $module_url;
        private $version;

        public $options_group_name = '';
        public $default_options = [];
        public $options = [];
        public $post_type_support = '';

        public $messages = [];

        public function __construct() {
            add_action('publishpress_plugin_screen', [$this, 'actLoadPluginScreen']);
        }

        function load_options($options_group_name) {
            $this->options_group_name = $options_group_name;
            
            $this->options = get_option(
                $options_group_name,
                new \stdClass()
            );

            foreach ($this->default_options as $default_key => $default_value) {
                if (! isset($this->options->$default_key)) {
                    $this->options->$default_key = $default_value;
                }
            }
        }

        /**
         * Collect all of the active post types for a given module
         *
         * @param object $module Module's data
         *
         * @return array $post_types
         *
         */
        public function get_enabled_post_types()
        {
            return (empty($this->options->post_types)) 
            ? [] 
            : array_keys(
                array_filter(
                    $this->options->post_types,
                    function($k) {
                        return !empty($k);
                    }
                )
            );
        }

        /**
         * Checks for the current post type
         *
         * @return string|null $post_type The post type we've found, or null if no post type
         */
        public function get_current_post_type()
        {
            global $post, $typenow, $pagenow, $current_screen;

            // get_post() needs a variable
            $post_id = isset($_REQUEST['post']) ? (int)$_REQUEST['post'] : false;

            if ($post && $post->post_type) {
                $post_type = $post->post_type;
            } elseif ($typenow) {
                $post_type = $typenow;
            } elseif ($current_screen && !empty($current_screen->post_type)) {
                $post_type = $current_screen->post_type;
            } elseif (isset($_REQUEST['post_type'])) {
                $post_type = sanitize_key($_REQUEST['post_type']);
            } elseif ('post.php' == $pagenow
                && $post_id
                && !empty(get_post($post_id)->post_type)) {
                $post_type = get_post($post_id)->post_type;
            } elseif ('edit.php' == $pagenow && empty($_REQUEST['post_type'])) {
                $post_type = 'post';
            } else {
                $post_type = null;
            }

            return $post_type;
        }

        function actLoadPluginScreen($module) {
            require_once(__DIR__ . '/ModuleAdminUI_Base.php');
            ModuleAdminUI_Base::instance($module);
        }

        public static function isAjax($action)
        {
            return defined('DOING_AJAX') && DOING_AJAX && $action && in_array(\PublishPress_Functions::REQUEST_var('action'), (array)$action);
        }

        // Wrapper to prevent poEdit from adding core WordPress strings to the plugin .po
        public static function __wp($string, $unused = '')
        {
            return __($string);
        }

        function REQUEST_var($var) {
            return (!empty($_REQUEST) && !empty($_REQUEST[$var])) ? $_REQUEST[$var] : '';
        }
    }
}
