<?php
/**
 * Plugin Name: PublishPress Statuses
 * Plugin URI:  https://publishpress.com/statuses
 * Description: Manage and create post statuses to customize your editorial workflow
 * Author: PublishPress
 * Author URI:  https://publishpress.com/
 * Version: 1.0-beta2
 *
 * Copyright (c) 2023 PublishPress
 *
 * GNU General Public License, Free Software Foundation <https://www.gnu.org/licenses/gpl-3.0.html>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     PublishPress Statuses
 * @author      PublishPress
 * @copyright   Copyright (c) 2023 PublishPress. All rights reserved.
 *
 **/

if (!defined('PUBLISHPRESS_STATUSES_VERSION')) {

    if (defined('PUBLISHPRESS_VERSION') && version_compare(PUBLISHPRESS_VERSION, '3.11-beta', '<')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf(
                    esc_html__('To use PublishPress Statuses, please upgrade PublishPress Plannner to version %s or higher.', 'publishpress_statuses'),
                    '3.11-beta'
                ); 
                ?></p>
            </div>
            <?php
        });

        $interrupt_load = true;
    }
    
    if (defined('PRESSPERMIT_VERSION') && version_compare(PRESSPERMIT_VERSION, '3.9-beta', '<')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf(
                    esc_html__('To use PublishPress Statuses, please upgrade PublishPress Permissions to version %s or higher.', 'publishpress_statuses'),
                    '3.9-beta'
                ); 
                ?></p>
            </div>
            <?php
        });
    
        $interrupt_load = true;
    }
    
    if (defined('PUBLISHPRESS_CAPS_VERSION') && version_compare(PUBLISHPRESS_CAPS_VERSION, '2.7.2-beta', '<')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf(
                    esc_html__('To use PublishPress Statuses, please upgrade PublishPress Capabilities to version %s or higher.', 'publishpress_statuses'),
                    '2.7.2-beta'
                ); 
                ?></p>
            </div>
            <?php
        });

        $interrupt_load = true;
    } 
    
    if (empty($interrupt_load)) {
        define('PUBLISHPRESS_STATUSES_VERSION', '1.0-beta2');

        define('PUBLISHPRESS_STATUSES_URL', trailingslashit(plugins_url('', __FILE__)));
        define('PUBLISHPRESS_STATUSES_DIR', __DIR__);

        require_once(__DIR__ . '/library/PublishPress_Functions.php');

        require_once(__DIR__ . '/library/publishpress-module/Module_Base.php');
        new \PublishPress\PPP_Module_Base();

        require_once(__DIR__ . '/PublishPress_Statuses.php');
        new \PublishPress_Statuses();

        add_action('plugins_loaded', function() {
            if (defined('PRESSPERMIT_VERSION')) {
                class_alias('\PressShack\LibWP', '\PublishPress_Statuses\PWP');

                if (class_exists('\PublishPress\Permissions\Statuses')) {
                    class_alias('\PublishPress\Permissions\Statuses', '\PublishPress_Statuses\PPS');
                }
            }
        });

        if (!class_exists('PP_Custom_Status') 
        && (defined('PRESSPERMIT_VERSION') || (!defined('PUBLISHPRESS_VERSION') || version_compare(PUBLISHPRESS_VERSION, '4.0', '<')))
        ) {
            class_alias('\PublishPress_Statuses', '\PP_Custom_Status');
        }
    }
}
