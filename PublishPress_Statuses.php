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

if (! class_exists('PublishPress_Statuses')) {
/**
 * class PublishPress_Statuses
 * Custom statuses make it simple to define the different stages in your publishing workflow.
 *
 */
class PublishPress_Statuses extends \PublishPress\PPP_Module_Base
{
    const MODULE_NAME = 'custom_status';
    const SETTINGS_SLUG = 'publishpress_custom_status_options';
    const MENU_SLUG = 'publishpress-statuses';

    private $post_type_support_slug = 'pp_custom_statuses'; // This has been plural in all of our docs

    const DEFAULT_STATUS = 'draft';

    const DEFAULT_COLOR = '#3859ff';
    const DEFAULT_ICON = 'dashicons-post-status';

    const TAXONOMY_PRE_PUBLISH = 'post_status';
    const TAXONOMY_PRIVACY = 'post_visibility_pp';
    const TAXONOMY_CORE_STATUS = 'post_status_core_wp_pp';
    const TAXONOMY_PSEUDO_STATUS = 'pseudo_status_pp';

    private $custom_statuses_cache = [];

    public $messages = [];

    public $module;
    public $doing_rest = false;

    private static $instance = null;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new \PublishPress_Statuses(false);
            self::$instance->load();
        }

        return self::$instance;
    }

    public static function doingREST()
    {
        return self::instance()->doing_rest;
    }

    public function clearStatusCache() {
        $this->custom_statuses_cache = [];
    }

    /**
     * Register the module with PublishPress but don't do anything else
     */
    public function __construct($do_load = true)
    {
        $this->version = PUBLISHPRESS_STATUSES_VERSION;

        if ($do_load) {
            $this->load();
        }
    }

    private function load() {
        if (is_admin()) {
            // Methods for handling the actions of creating, making default, and deleting post stati

            // @todo: REST
            if (!empty($_REQUEST['page']) && in_array($_REQUEST['page'], ['publishpress-statuses'])) { 
                add_action('init', [$this, 'handle_add_custom_status']);
                add_action('init', [$this, 'handle_edit_custom_status']);
                add_action('init', [$this, 'handle_delete_custom_status']);

                add_action('admin_init', [$this, 'handle_settings'], 100);
            }

            add_action('wp_ajax_pp_get_selectable_statuses', [$this, 'get_ajax_selectable_statuses']);
            add_action('wp_ajax_pp_set_workflow_action', [$this, 'set_workflow_action']);

            add_action('wp_ajax_pp_update_status_positions', [$this, 'handle_ajax_update_status_positions']);
            add_action('wp_ajax_pp_statuses_toggle_section', [$this, 'handle_ajax_pp_statuses_toggle_section']);
            add_action('wp_ajax_pp_delete_custom_status', [$this, 'handle_ajax_delete_custom_status']);

            add_filter('presspermit_get_post_statuses', [$this, 'flt_get_post_statuses'], 99, 4);
            add_filter('presspermit_order_statuses', [$this, 'orderStatuses'], 10, 2);

            add_action('init', function() {
                if (!empty($_REQUEST['page']) && ('publishpress-statuses' == $_REQUEST['page']) && !empty($_REQUEST['action']) && ('edit-status' == $_REQUEST['action'])) {
                    $status_name = sanitize_key($_REQUEST['name']);
                    if ($status_obj = get_post_status_object($status_name)) {
                        $this->title = sprintf(__('Edit Post Status: %s', 'publishpress-statuses'), $status_obj->label);
                    }
                }
            }, 999);
        }

        add_filter('rest_pre_dispatch', [$this, 'fltRestPreDispatch'], 10, 3);
        add_action('rest_api_init', [$this, 'actRestInit'], 1);
        add_filter('pre_post_status', [$this, 'fltPostStatus'], 20);

        // Register the module with PublishPress
        
        $this->slug = 'publishpress_statuses';
        $this->name = 'publishpress_statuses';
        
        if (!empty($_REQUEST['page']) && ('publishpress-statuses' == $_REQUEST['page']) && !empty($_REQUEST['action']) && ('edit-status' == $_REQUEST['action'])) {
            $this->title = __('Edit Post Status', 'publishpress-statuses');

        } elseif (!empty($_REQUEST['page']) && ('publishpress-statuses' == $_REQUEST['page']) && !empty($_REQUEST['action']) && ('add-new' == $_REQUEST['action'])) {
            if (!empty($_REQUEST['taxonomy'])) {
                if ($tx = get_taxonomy(sanitize_key($_REQUEST['taxonomy']))) {
                    $this->title = sprintf(
                        __('Add %s Status', 'publishpress_statuses'),
                        $tx->label
                    );
                }
            } 
            
            if (empty($this->title)) {
                $this->title = __('Add Post Status', 'publishpress-statuses');
            }
        } elseif (!empty($_REQUEST['page']) && 'publishpress-hub' == $_REQUEST['page']) {
            $this->title = __('PublishPress Hub', 'publishpress-statuses');
        } else {
            $this->title = __('Post Statuses', 'publishpress-statuses');
        }

        $this->default_options = [
            'enabled' => 1,
            'post_types' => [
                'post' => 1,
                'page' => 1,
            ],
            'supplemental_cap_moderate_any' => 0,
            'moderation_statuses_default_by_sequence' => 0
        ];

        $this->post_type_support_slug = 'pp_custom_statuses'; // This has been plural in all of our docs

        $args = [
            'title' => __('PublishPress Statuses', 'publishpress-statuses'),
            'short_description' => false,
            'extended_description' => false,
            'module_url' => PUBLISHPRESS_STATUSES_URL,
            'icon_class' => 'dashicons dashicons-tag',
            'slug' => 'custom-status',
            'default_options' => $this->default_options,
            'post_type_support' => 'pp_custom_statuses', // This has been plural in all of our docs
            'configure_page_cb' => 'print_configure_view',
            'configure_link_text' => __('Edit Statuses', 'publishpress-statuses'),
            'messages' => [
                'status-added' => __('Post status created.', 'publishpress-statuses'),
                'status-updated' => __('Post status updated.', 'publishpress-statuses'),
                'status-missing' => __("Post status doesn't exist.", 'publishpress-statuses'),
                'default-status-changed' => __('Default post status has been changed.', 'publishpress-statuses'),
                'term-updated' => __("Post status updated.", 'publishpress-statuses'),
                'status-deleted' => __('Post status deleted.', 'publishpress-statuses'),
                'status-position-updated' => __("Status order updated.", 'publishpress-statuses'),
            ],
            'autoload' => false,
            'settings_help_tab' => [
                'id' => 'pp-custom-status-overview',
                'title' => __('Overview', 'publishpress-statuses'),
                'content' => __(
                    '<p>PublishPress custom statuses allow you to define the most important stages of your editorial workflow. Out of the box, WordPress only offers "Draft" and "Pending Review" as post states. With custom statuses, you can create your own post states like "In Progress", "Pitch", or "Waiting for Edit" and keep or delete the originals. You can also drag and drop statuses to set the best order for your workflow.</p><p>Custom statuses are fully integrated into the rest of PublishPress and the WordPress admin. On the calendar and content overview, you can filter your view to see only posts of a specific post state. Furthermore, email notifications can be sent to a specific group of users when a post changes state.</p>',
                    'publishpress-statuses'
                ),
            ],
            'settings_help_sidebar' => __(
                '<p><strong>For more information:</strong></p><p><a href="https://publishpress.com/features/custom-statuses/">Custom Status Documentation</a></p><p><a href="https://github.com/ostraining/PublishPress">PublishPress on Github</a></p>',
                'publishpress-statuses'
            ),
            'options_page' => false,
        ];

        $this->module = (object) array_merge($args, ['name' => 'custom-status']);

        $this->load_options(self::SETTINGS_SLUG);

        if (did_action('init')) {
            $this->init();
        } else {
            $init_priority = (defined('PUBLISHPRESS_ACTION_PRIORITY_INIT')) ? PUBLISHPRESS_ACTION_PRIORITY_INIT : 10;
            add_action('init', [$this, 'init'], $init_priority);
        }
    }

    /**
     * Initialize the PP_Custom_Status class if the module is active
     */
    public function init()
    {
        global $pagenow;

        static $done;

        if (!empty($done)) { // Avoid redundant execution with Post Editor
            return;
        } else {
            $done = true;
        }

        if (empty($this->version)) {
            $this->load();
        }

        // Register new taxonomy so that we can store all our fancy new custom statuses (or is it stati?)
        if (! taxonomy_exists(self::TAXONOMY_PRE_PUBLISH)) {
            register_taxonomy(
                self::TAXONOMY_PRE_PUBLISH,
                'post',
                [
                    'hierarchical' => false,
                    'update_count_callback' => '_update_post_term_count',
                    'label' => __('Pre-Publication Statuses', 'publishpress-statuses'),
                    'query_var' => false,
                    'rewrite' => false,
                    'show_ui' => false,
                ]
            );
        }

        if (! taxonomy_exists(self::TAXONOMY_CORE_STATUS)) {
            register_taxonomy(
                self::TAXONOMY_CORE_STATUS,
                [],
                [
                    'hierarchical' => false,
                    'update_count_callback' => '_update_post_term_count',
                    'label' => __('Core Post Statuses', 'publishpress-statuses'),
                    'query_var' => false,
                    'rewrite' => false,
                    'show_ui' => false,
                ]
            );
        }

        if (self::isStatusManagement()) {
            if (! taxonomy_exists(self::TAXONOMY_PSEUDO_STATUS)) {
                register_taxonomy(
                    self::TAXONOMY_PSEUDO_STATUS,
                    [],
                    [
                        'hierarchical' => false,
                        'update_count_callback' => '_update_post_term_count',
                        'label' => __('Pseudo Statuses', 'publishpress-statuses'),
                        'query_var' => false,
                        'rewrite' => false,
                        'show_ui' => false,
                    ]
                );
            }
        } else {
            $disable_statuses = self::disable_custom_statuses_for_post_type();
        }

        do_action('publishpress_statuses_register_taxonomies');

        $statuses = $this->getPostStatuses([], 'object', ['load' => true]);

        // Register custom statuses, which are stored as taxonomy terms
        $this->register_moderation_statuses($statuses);

        // @todo: check for disable
        // Register visibility statuses, which are stored as taxonomy terms
        $this->register_visibility_statuses($statuses);

        $this->set_core_status_properties($statuses);

        require_once(__DIR__ . '/Workarounds.php');
        new \PublishPress_Statuses\Workarounds();

        if (is_admin()) {
            // WordPress Dashboard integration
            require_once(__DIR__ . '/Admin.php');
            new \PublishPress_Statuses\Admin();

            if (empty($disable_statuses)) {
                // Implementation for Posts screen, Post Editor
                if (!empty($pagenow) && in_array($pagenow, ['edit.php', 'post.php', 'post-new.php'])) {
                    require_once(__DIR__ . '/PostsListing.php');
                    new \PublishPress_Statuses\PostsListing();
                }

                // Scripts for Posts Editor only
                if (!empty($pagenow) && in_array($pagenow, ['post.php', 'post-new.php'])) {
                    require_once(__DIR__ . '/PostEdit.php');
                    new \PublishPress_Statuses\PostEdit();
                }
            }

            // Status Administration
            if (!empty($_REQUEST['page']) && ('publishpress-statuses' == $_REQUEST['page'])) {
                // already loaded by menu hook
                require_once(__DIR__ . '/StatusesUI.php');
                new \PublishPress_Statuses\StatusesUI();
            }
        }
    }

    /**
     * Handles a form's POST request to add a custom status
     */
    public function handle_add_custom_status()
    {
        if (isset($_POST['submit'], $_POST['action']) && !isset($_GET['settings_module']) && ($_POST['action'] === 'add-status')) {
            require_once(__DIR__ . '/StatusHandler.php');
            \PublishPress_Statuses\StatusHandler::handleAddCustomStatus();
        }
    }

    /**
     * Handles a POST request to edit a custom status
     */
    public function handle_edit_custom_status()
    {
        if (isset($_POST['submit'], $_POST['action']) && !isset($_GET['settings_module']) && ($_POST['action'] === 'edit-status')) {
            require_once(__DIR__ . '/StatusHandler.php');
            \PublishPress_Statuses\StatusHandler::handleEditCustomStatus();
        }
    }

    /**
     * Handles a GET request to delete a specific term
     *
     * @since 0.7
     */
    public function handle_delete_custom_status()
    {
        if (isset($_POST['submit'], $_POST['action']) && !isset($_GET['settings_module']) && ($_POST['action'] === 'delete-status')) {
            require_once(__DIR__ . '/StatusHandler.php');
            \PublishPress_Statuses\StatusHandler::handleDeleteCustomStatus();
        }
    }

    public function handle_ajax_delete_custom_status()
    {
        require_once(__DIR__ . '/StatusHandler.php');
        \PublishPress_Statuses\StatusHandler::handleAjaxDeleteStatus();
    }

    /**
     * Handles a POST request to edit general status settings
     */
    public function handle_settings()
    {
        if (isset($_POST['submit'], $_POST['action']) && (isset($_POST['option_page']) && ('publishpress_custom_status_options' == $_POST['option_page']))) { //&& !isset($_GET['settings_module']) && ($_POST['action'] === 'edit-settings')) {
            require_once(__DIR__ . '/StatusHandler.php');
            \PublishPress_Statuses\StatusHandler::settings_validate_and_save();
        }
    }

    /**
     * Handle an ajax request to update the order of custom statuses
     *
     * @since 0.7
     */
    public function handle_ajax_update_status_positions()
    {
        require_once(__DIR__ . '/StatusHandler.php');
        \PublishPress_Statuses\StatusHandler::handleAjaxUpdateStatusPositions();
    }

    public function handle_ajax_pp_statuses_toggle_section()
    {
        require_once(__DIR__ . '/StatusHandler.php');
        \PublishPress_Statuses\StatusHandler::handleAjaxToggleStatusSection();
    }

    public function get_ajax_selectable_statuses()
    {
        if (!empty($_REQUEST['post_id'])) {
            if (!wp_verify_nonce($_POST['pp_nonce'],'pp-custom-statuses-nonce')) {
                exit;
            }

            if (!current_user_can('edit_post', intval($_REQUEST['post_id']))) {
                exit;
            }

            $post_id = (int) $_REQUEST['post_id'];
            require_once(__DIR__ . '/PostEdit.php');

            $args = [];
            $params = [];

            if (!empty($_REQUEST['selected_status'])) {
                $args['post_status'] = sanitize_key($_REQUEST['selected_status']);

                // @todo: separate ajax call for setting status
                if ($status_obj = get_post_status_object($args['post_status'])) {
                    if ($_post = get_post($post_id)) {
                        if (\PublishPress_Statuses::haveStatusPermission('set_status', $_post->post_type, $status_obj->name)) {
                            wp_update_post(['ID' => $post_id, 'post_status' => $status_obj->name]);
                        }

                        $next_status_obj = \PublishPress_Statuses::getNextStatusObject(0, ['default_by_sequence' => true, 'post_type' => $_post->post_type, 'post_status' => $status_obj->name]);
                        $max_status_obj = \PublishPress_Statuses::getNextStatusObject($post_id, ['default_by_sequence' => false, 'post_type' => $_post->post_type, 'post_status' => $status_obj->name]);

                        if ($next_status_obj) {
                            $params = [
                                'nextStatus' => $next_status_obj->name
                            ];
                        } else {
                            $params = [
                                'nextStatus' => $status_obj->name
                            ];
                        }

                        if ($max_status_obj) {
                            $params['maxStatus'] =  $max_status_obj->name;
                        }
                    }
                }
            }

            $statuses = array_keys(\PublishPress_Statuses\Admin::get_selectable_statuses($post_id, $args));
            \PublishPress_Functions::printAjaxResponse('success', '', $statuses, $params);
        } else {
            \PublishPress_Functions::printAjaxResponse('success', '', [], []);
        }

        exit;
    }

    public function set_workflow_action($action) {
        global $current_user;

        if (!empty($_REQUEST['post_id']) && !empty($_REQUEST['workflow_action'])) {
            if (!wp_verify_nonce($_POST['pp_nonce'],'pp-custom-statuses-nonce')) {
                exit;
            }

            if (!current_user_can('edit_post', intval($_REQUEST['post_id']))) {
                exit;
            }

            update_user_meta($current_user->ID, "_pp_statuses_workflow_action_" . intval($_REQUEST['post_id']), sanitize_key($_REQUEST['workflow_action']));

            \PublishPress_Functions::printAjaxResponse('success', '', [], []);
        }
    }

    public static function isStatusManagement() {
        return
            (is_admin() && (!empty($_REQUEST['page']) && in_array($_REQUEST['page'], ['publishpress-statuses', 'pp-capabilities'])))
            || (
                isset($_SERVER['SCRIPT_NAME']) 
                && false !== strpos(sanitize_text_field($_SERVER['SCRIPT_NAME']), 'admin-ajax.php') 
                && \PublishPress_Functions::is_REQUEST('action', ['pp_update_status_positions', 'pp_statuses_toggle', 'pp_delete_custom_status'])
            );
    }

    /**
     * @return array
     */
    protected function get_default_statuses($taxonomy, $args = []) {

        switch ($taxonomy) {
            case self::TAXONOMY_CORE_STATUS : 
                $statuses = [
                    'draft' =>  (object) [
                        'label' => 'Draft',             // Replace with WP translation below
                        'description' => '-',
                        'color' => '#aaaaaa',
                        'icon' => 'dashicons-media-default',
                        'position' => 0,
                        'order' => 0,
                        '_builtin' => true,
                    ],

                    'pending' => (object) [
                        'label' => 'Pending Review',        // Replace with WP translation below
                        'label_friendly' => __('Pending Review'),
                        'description' => '-',
                        'color' => '#ff8300',
                        'icon' => 'dashicons-clock',
                        'position' => 4,
                        'order' => 200,
                        '_builtin' => true,
                        'moderation' => true,
                    ],

                    'future' => (object) [
                        'label' => 'Scheduled',             // Replace with WP translation below
                        'description' => '-',
                        'color' => '#a996ff',
                        'icon' => 'dashicons-calendar-alt',
                        'position' => 7,
                        'order' => 700,
                        '_builtin' => true,
                        //'moderation' => true,
                    ],

                    'publish' => (object) [
                        'label' => 'Published',             // Replace with WP translation below
                        'description' => '-',
                        'color' => self::DEFAULT_COLOR,
                        'icon' => 'dashicons-yes',
                        'position' => 8,
                        'order' => 800,
                        '_builtin' => true,
                        'public' => true,
                    ],

                    'private' => (object) [
                        'label' => 'Privately Published',   // Replace with WP translation below
                        'description' => '-',
                        'color' => '#ee0000',
                        'icon' => 'dashicons-lock',
                        'position' => 9,
                        'order' => 900,
                        '_builtin' => true,
                        'private' => true,
                    ]
                ];

                break;

            case self::TAXONOMY_PRE_PUBLISH :
                $statuses = [
                    'pitch' => (object) [
                        'label' => __('Pitch', 'publishpress-statuses'),
                        'labels' => (object) ['publish' => __('Throw Pitch', 'publishpress-statuses')],
                        'description' => __('Idea proposed; waiting for acceptance.', 'publishpress-statuses'),
                        'color' => '#f4c5b0',
                        'icon' => 'dashicons-lightbulb',
                        'position' => 1,
                        'order' => 100,
                        'moderation' => true,
                        'pp_builtin' => true,
                    ],
    
                    'assigned' => (object) [
                        'label' => __('Assigned', 'publishpress-statuses'),
                        'labels' => (object) ['publish' => __('Assign', 'publishpress-statuses')],
                        'description' => __('Post idea assigned to writer.', 'publishpress-statuses'),
                        'color' => '#00bcc5',
                        'icon' => 'dashicons-admin-users',
                        'position' => 2,
                        'order' => 200,
                        'moderation' => true,
                        'pp_builtin' => true,
                    ],
    
                    'in-progress' => (object) [
                        'label' => __('In Progress', 'publishpress-statuses'),
                        'labels' => (object) ['publish' => __('Mark in Progress', 'publishpress-statuses')],
                        'description' => __('Writer is working on the post.', 'publishpress-statuses'),
                        'color' => '#ccc500',
                        'icon' => 'dashicons-performance',
                        'position' => 3,
                        'order' => 300,
                        'moderation' => true,
                        'pp_builtin' => true,
                    ],
    
                    'approved' => (object) [
                        'label' => __('Approved', 'publishpress-statuses'),
                        'labels' => (object) ['publish' => __('Approve', 'publishpress-statuses')],
                        'description' => '-',
                        'color' => '#3ffc3f',
                        'icon' => 'dashicons-yes-alt',
                        'position' => 5,
                        'order' => 250,
                        'moderation' => true,
                        'pp_builtin' => true,
                    ],
                ];

                break;

            case self::TAXONOMY_PSEUDO_STATUS :
                $statuses = [
                    // [Fake status to support organization by table position re-ordering]: "Pre-Publication Statuses:"
                    '_pre-publish-alternate' => (object) [
                        'label' => __('Alternate Pre-Publication Workflow:', 'publishpress-statuses'),
                        'description' => '-',
                        'color' => '',
                        'icon' => '',
                        'position' => 6,
                        'order' => 300,
                        'moderation' => true,
                        'alternate' => true,
                        'disabled' => true,
                    ],
    
                    // [Fake status to support organization by table position re-oredering]: "Disabled Statuses:"
                    '_disabled' => (object) [
                        'label' => __('Disabled Statuses:', 'publishpress-statuses'),
                        'description' => '-',
                        'color' => '',
                        'icon' => '',
                        'position' => 13,
                        'order' => 300,
                        'moderation' => false,
                        'disabled' => true,
                    ]
                ];

                break;

            default:
                $statuses = apply_filters('publishpress_statuses_get_default_statuses', [], $taxonomy);
        }

        foreach (array_keys($statuses) as $slug) {
            $statuses[$slug]->name = $slug;
            $statuses[$slug]->slug = $slug;  // @todo: eliminate in favor of name?
            
            $statuses[$slug]->taxonomy = $taxonomy;
            $statuses[$slug]->disabled = false;
        }

        return $statuses;
    }

    private function apply_default_status_properties($status) {
        foreach (['public', 'private', 'protected', 'moderation', 'label', 'description', 'disabled'] as $prop) {
            if (!isset($status->$prop)) {
                switch ($prop) {
                    case 'label':
                        $status->$prop = $slug;
                        break;

                    case 'public':
                    case 'private':
                    case 'protected':
                    case 'moderation':
                    case 'disabled':
                        $status->$prop = false;
                        break;

                    default:
                        $status->$prop = '';
                }
            }
        }

        if (is_admin()) {
            require_once(__DIR__ . '/Admin.php');

            $status = \PublishPress_Statuses\Admin::set_status_labels($status);
        }

        return $status;
    }

    private function set_core_status_properties($statuses) {
        global $wp_post_statuses;
        
        foreach ($statuses as $status) {
            if (in_array($status->name, ['pending'])) {
                foreach (['capability_status'] as $prop) {
                    if (!empty($status->$prop)) {
                        $wp_post_statuses[$status->name]->$prop = $status->$prop;
                    }
                }
            }
        }

        if (is_admin()) {
            $wp_post_statuses['publish']->labels->publish = esc_attr(self::__wp('Publish'));
            $wp_post_statuses['future']->labels->publish = esc_attr(self::__wp('Schedule'));
    
            if (empty($wp_post_statuses['pending']->labels->publish)) {
                $wp_post_statuses['pending']->labels->publish = esc_attr(self::__wp('Submit for Review'));
            }

            $wp_post_statuses['draft']->labels->save_as = esc_attr(self::__wp('Save Draft'));
            $wp_post_statuses['draft']->labels->publish = esc_attr(self::__wp('Save Draft'));
    
            if (empty($wp_post_statuses['pending']->labels->caption)) {
                $wp_post_statuses['pending']->labels->caption = self::__wp('Pending Review');
            }

            $wp_post_statuses['private']->labels->caption = self::__wp('Privately Published');

            //$wp_post_statuses['auto-draft']->labels->caption = self::__wp('Draft');
        }
    }

    /**
     * Makes the call to register_post_status to register the user's custom statuses.
     */
    private function register_moderation_statuses($statuses)
    {
        if (function_exists('register_post_status')) {
            foreach ($statuses as $status) {
                // Ignore visibility statues and all core statuses, which are registered elsewhere.
                if (!empty($status->_builtin) || !empty($status->public) || !empty($status->private) 
                || in_array($status->slug, ['publish', 'private', 'pending', 'draft', 'future'])
                || !empty($status->disabled)
                || in_array($status->slug, ['_pre-publish-alternate', '_disabled'])
                ) {
                    continue;
                }

                $postStatusArgs = apply_filters('publishpress_new_custom_status_args', $this->moderation_status_properties($status), $status);

                register_post_status($status->slug, $postStatusArgs);
            }
        }
    }

    private function moderation_status_properties($status) {
        $label = (!empty($status->label)) ? $status->label : $status->name;
        
        return [
            'label' => $label,
            'protected' => true,
            'date_floating' => true,
            '_builtin' => false,
            'moderation' => !empty($status->moderation),
            'alternate' => !empty($status->alternate),
            'disabled' => !empty($status->disabled),
            'status_parent' => !empty($status->status_parent) ? $status->status_parent : '',
            'post_type' => (!empty($status->post_type)) ? $status->post_type : [],
            'labels' => (!empty($status->labels)) ? $status->labels : (object) ['publish' => '', 'save_as' => '', 'name' => $label],
            'label_count' => _n_noop(
                "{$status->label} <span class='count'>(%s)</span>",
                "{$status->label} <span class='count'>(%s)</span>"
            ),
        ];
    }

    private function apply_moderation_status_properties($status) {
        foreach ($this->moderation_status_properties($status) as $prop =>$val) {
            if (!isset($status->$prop)) {
                $status->$prop = $val;
            }
        }

        return apply_filters('publishpress_status_properties', $status);
    }

    /**
     * Makes the call to register_post_status to register the user's post visibility statuses.
     *
     * @param array $args
     */
    private function register_visibility_statuses($statuses)
    {
        do_action('publishpress_statuses_register_visibility_statuses', $statuses);
    }

    public static function visibility_status_properties($status) {
        $label = (!empty($status->label)) ? $status->label : $status->name;
        
        return [
            'label' => $label,
            'protected' => true,
            'date_floating' => true,
            '_builtin' => false,
            'pp_builtin' => true,
            'private' => true,
            'post_type' => (!empty($status->post_type)) ? $status->post_type : [],
            'labels' => (!empty($status->labels)) ? $status->labels : (object) ['publish' => '', 'save_as' => '', 'name' => $label],
            'label_count' => _n_noop(
                "{$status->label} <span class='count'>(%s)</span>",
                "{$status->label} <span class='count'>(%s)</span>"
            ),
        ];
    }

    private function apply_visibility_status_properties($status) {
        foreach (self::visibility_status_properties($status) as $prop =>$val) {
            if (!isset($status->$prop)) {
                $status->$prop = $val;
            }
        }

        return $status;
    }

    public static function getCurrentPostType() {
        return self::instance()->get_current_post_type();
    }

    public static function DisabledForPostType($post_type = null) {
        return self::disable_custom_statuses_for_post_type($post_type);
    }

    public static function getEnabledPostTypes() {
        return self::instance()->get_enabled_post_types();
    }

    /**
     * Whether custom post statuses should be disabled for this post type.
     * Used to stop custom statuses from being registered for post types that don't support them.
     *
     * @return bool
     * @since 0.7.5
     */
    public static function disable_custom_statuses_for_post_type($post_type = null)
    {
        global $pagenow;

        // Only allow deregistering on 'edit.php' and 'post.php'
        if (self::isStatusManagement()) {
            return false;
        }

        if (is_null($post_type)) {
            $post_type = self::getCurrentPostType();
        }

        // Always allow for the notification workflows
        if (defined('PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW')) {
            if (PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW === $post_type) {
                return false;
            }
        }

        if ($post_type && !in_array($post_type, self::getEnabledPostTypes())) {
            return true;
        }

        return false;
    }

    public static function get_status_property($status, $property)
    {
        if (! isset($status->$property)) {
            if ($property === 'color') {
                $value = self::DEFAULT_COLOR;
            } else {
                $value = '';
            }
        } else {
            $value = $status->$property;
        }

        return $value;
    }

    /**
     * Get core's 'draft' and 'pending' post statuses, but include our special attributes
     *
     * @return array
     * @since 0.8.1
     *
     */
    protected function getCorePostStatuses($return_args = [])
    {
        global $wp_post_statuses;

        // $return_args: return array structure
        //
        // support get_post_stati() argument structure
        if (is_string($return_args)) {
            $return_args = ['output' => $return_args];

        } elseif (!is_array($return_args)) {
            $return_args = [];
        }

        foreach (
            [
                'output' => 'names',
                'return_key' => 'name'
            ] 
        as $prop => $default_val) {
            if (!isset($return_args[$prop])) {
                $return_args[$prop] = $default_val;
            }
        }

        $statuses = self::get_default_statuses(self::TAXONOMY_CORE_STATUS);

        /*
        $statuses = [
            'draft' => (object)[
                'label'        => __('Draft'),
                'description' => '',
                'position'    => 1,
            ],

            'pending' => (object)[
                'label'        => __('Pending Review'),
                'description' => '',
                'position'    => 2,
            ],

            'publish' => (object)[
                'label'        => __('Published'),
                'description' => '',
                'position'    => 3,
            ],
        ];
        */

        foreach (array_keys($statuses) as $status_name) {
            $statuses[$status_name]->slug = $status_name;
            $statuses[$status_name]->name = $status_name;

            $statuses[$status_name] = $this->apply_default_status_properties($statuses[$status_name]);

            $status_name = $statuses[$status_name]->name;

            //if (!empty($args['mirror_to_wp_statuses'])) {
                foreach (get_object_vars($statuses[$status_name]) as $prop => $val) {
                    if (isset($wp_post_statuses[$status_name])) {
                        if (!isset($wp_post_statuses[$status_name]->$prop)) {
                            $wp_post_statuses[$status_name]->$prop = $val;
                        }
                    }
                }
            //}
        }

        return ($return_args['output'] == 'object') ? $statuses : array_keys($statuses);
    }

    /**
     * BACK COMPAT for PublishPress Planner: Returns status name as 'slug', status label as 'name'
     * 
     * If the module is disabled, we display only the native statuses.
     *
     * @return array
     */
    public function get_post_statuses($args = [])
    {
        $statuses = $this->getPostStatuses($args);

        foreach ($statuses as $status_name => $status) {
            $statuses[$status_name]->slug = $status->name;
            $statuses[$status_name]->name = $status->label;
        }

        return $statuses;
    }

    public static function getCustomStatuses($status_args = [], $return_args = [], $function_args = []) {
        $status_args = array_merge($status_args, ['_builtin' => false]);

        if (!$return_args) {
            $return_args = 'object';
        }

        return self::instance()->getPostStatuses($status_args, $return_args, $function_args);
    }

    public static function getPostStati($status_args = [], $return_args = [], $function_args = []) {
        //$args = array_merge($args, ['_builtin' => false]);
        return self::instance()->getPostStatuses($status_args, $return_args, $function_args);
    }

    /**
     * Get all custom statuses as an ordered array
     *
     * @param array|string $statuses
     * @param array $args
     *
     * @return array $statuses All of the statuses
     */
    public function getPostStatuses($status_args = [], $return_args = [], $function_args = [])
    {
        // $status_args: filtering of return array based on status properties, applied outside the cache by function process_return_array()
        //

        // $return_args: return array structure, applied outside the cache by function process_return_array()
        //

        // $function_args: variations on the logic for populating or ordering the statuses array, cause a separate statuses cache entry    
        //
        foreach (
            [
                'show_disabled' => is_admin() && !empty($_REQUEST['page']) && ('publishpress-statuses' == $_REQUEST['page'])
            ] 
        as $prop => $default_val) {
            if (!isset($function_args[$prop])) {
                $function_args[$prop] = $default_val;
            }
        }
        
        if (self::disable_custom_statuses_for_post_type()) {
            return $this->getCorePostStatuses($return_args);
        }

        if (!isset($function_args['show_disabled'])) {
            $function_args['show_disabled'] = is_admin() && !empty($_REQUEST['page']) && ('publishpress-statuses' == $_REQUEST['page']);
        }

        //   On the Edit Post screen, the Status Control plugin has two implementations for statuses without a non-zero order:
        //      * No status_parent set: Excluded from automatic workflow status progression (but available for manual selection)
        //      * status_parent set: Normally unavailable, but available for manual selection after a parent status is selected

        // Internal object cache for repeat requests
        $arg_hash = md5(serialize($function_args));
        if (! empty($this->custom_statuses_cache[$arg_hash])) {
            return $this->process_return_array($this->custom_statuses_cache[$arg_hash], $status_args, $return_args, $function_args);
        }

        $mirror_to_wp_statuses = true; // empty($function_args);
        $core_statuses = $this->get_default_statuses(self::TAXONOMY_CORE_STATUS, compact('mirror_to_wp_statuses'));
        $pseudo_statuses = $this->get_default_statuses(self::TAXONOMY_PSEUDO_STATUS);
        $default_moderation_statuses = $this->get_default_statuses(self::TAXONOMY_PRE_PUBLISH, compact('mirror_to_wp_statuses'));
        $default_privacy_statuses = $this->get_default_statuses(self::TAXONOMY_PRIVACY, compact('mirror_to_wp_statuses'));

        $all_statuses = array_merge($core_statuses, $pseudo_statuses, $default_moderation_statuses, $default_privacy_statuses);

        $disabled_statuses = (array) get_option('publishpress_disabled_statuses');

        $positions = get_option('publishpress_status_positions');

        $stored_status_positions = (is_array($positions) && $positions) ? array_flip($positions) : [];

        $stored_status_terms = [];

        // Merge stored positions with defaults
        foreach ($all_statuses as $status_name => $status) {
            if (empty($stored_status_positions[$status_name])) {
                $stored_status_positions[$status_name] = (!empty($status->position)) ? $status->position : 0;
            }
        }

        // We are using the terms and term_taxonomy tables to store and configure several types of post statuses, but disregarding term_id and term_taxonomy_id. 
        // Status name (slug) is the unique key (as used in the post_status column of the posts table), and there is no expectation to join the term tables to post queries for status filtering.
        foreach ([self::TAXONOMY_PRE_PUBLISH, self::TAXONOMY_PRIVACY, self::TAXONOMY_CORE_STATUS] as $taxonomy) {
            $stored_status_terms[$taxonomy] = [];

            $_terms = get_terms(
                $taxonomy, 
                ['hide_empty' => false] // @todo: support other args?
            );

            if (is_wp_error($_terms) || empty($_terms)) {
                continue;
            }

            foreach ($_terms as $term) {
                if (isset($stored_status_terms[$taxonomy][$term->slug]) || ('pending-review' == $term->slug)) {
                    continue;
                }

                // Map taxonomy schema columns to Post Status properties
                $term->label = (!empty($core_statuses[$term->slug])) ? $core_statuses[$term->slug]->label : $term->name;

                $term->name = $term->slug;
                
                // Unencode pseudo term meta values stored in description column
                $unencoded_description = self::get_unencoded_description($term->description);
                if (is_array($unencoded_description)) {
                    $unencoded_description = array_diff_key(
                        $unencoded_description,
                    
                        array_fill_keys(    // Strip out status properties no longer stored to description field
                            ['position', 'order'], 
                            true
                        ),
                        array_fill_keys(    // Prevent some status properties from being modified by description field encoding
                            ['public', 'private', 'moderation', 'protected'],
                            true
                        )
                    );

                    foreach ($unencoded_description as $descriptionKey => $value) {
                        $term->$descriptionKey = $value;
                    }
                }

                // Hardcode properties for some statuses 
                if ('draft' == $term->slug) {
                    $term->position = 0;
                }

                if (self::TAXONOMY_PRIVACY == $taxonomy) {
                    $term->private = true;
                } elseif (self::TAXONOMY_PRE_PUBLISH == $taxonomy) {
                    $term->moderation = true;
                }

                // (@todo: support status slug (name) change using term_taxonomy_id?)
                $term = (object) array_diff_key(
                    (array) $term,
                    array_fill_keys(    // Strip out most properties related to taxonomy storage schema. status_parent property is encoded in description field using parent status_name.
                        ['term_group', 'term_id', 'taxonomy', 'count', 'parent', 'filter'], 
                        true
                    )
                );

                $stored_status_terms[$taxonomy][$term->slug] = $term;

                foreach($stored_status_terms[$taxonomy] as $status_name => $stored_status) {
                    if (!isset($all_statuses[$status_name])) {
                        $all_statuses[$status_name] = $stored_status_terms[$taxonomy][$status_name];
                    } else {
                        foreach (get_object_vars($stored_status_terms[$taxonomy][$status_name]) as $prop => $value) {
                            $all_statuses[$status_name]->$prop = $value;
                        }
                    }
                }
            }
        }

        // retore previously merged status positions (@todo: restore any other properties?)
        foreach ($all_statuses as $status_name => $status) {
            if (isset($stored_status_positions[$status_name])) {
                // Deal with deactivation / reactivation of custom privacy statuses due to Status Control module deactivation / re-activation
                //
                // (@todo: cleaner solution)

                if (empty($all_statuses[$status_name]->private)) {
                    // This is a non-private status whose position may have been artificially backed up from the disabled section into the private section
                    if ('pending' == $status_name) {
                        // Pending status cannot be moved out of standard Pre-Publication workflow
                        if ($stored_status_positions[$status_name] >= $stored_status_positions['_pre-publish-alternate']) {
                            $stored_status_positions[$status_name] = 1;
                            $all_statuses[$status_name]->disabled = false;
                        }
                    } elseif (($stored_status_positions[$status_name] >= $stored_status_positions['private']) && ('_disabled' != $status_name)) {
                        $all_statuses[$status_name]->disabled = true;
                    }
                } else {
                    // This is a private status whose position may have been artificially advanced from the private section into the disabled section
                    if (($stored_status_positions[$status_name] < $stored_status_positions['private']) && !empty($stored_status_positions[$status_name])
                    ) {
                        $stored_status_positions[$status_name] = $stored_status_positions['private'];
                    }
                }

                $all_statuses[$status_name]->position = $stored_status_positions[$status_name];

            } else {
                // position has not been stored for this status, so default into correct section

                if (!empty($all_statuses[$status_name]->private)) {
                    $all_statuses[$status_name]->position = $all_statuses['private']->position;
                    $stored_status_positions[$status_name] = $all_statuses['private']->position;
                } else {
                    $all_statuses[$status_name]->position = $all_statuses['_pre-publish-alternate']->position;
                    $stored_status_positions[$status_name] = $all_statuses['_pre-publish-alternate']->position;
                }
            }

            if (!empty($status->moderation)) {
                $all_statuses[$status_name] = $this->apply_moderation_status_properties($all_statuses[$status_name]);
            } elseif (!empty($status->private)) {
                $all_statuses[$status_name] = $this->apply_visibility_status_properties($all_statuses[$status_name]);
            }
        }

        // establish the position of the disabled section
        $privacy_statuses = array_merge($default_privacy_statuses, $stored_status_terms[self::TAXONOMY_PRIVACY]);
        $all_statuses['_disabled']->position = $all_statuses['private']->position + count($privacy_statuses) + 1;
        $all_statuses['_disabled']->disabled = true;

        // A status can't be its own parent
        foreach ($all_statuses as $status_name => $status) {
            if (!empty($status->status_parent) && ($status_name == $status->status_parent)) {
                $all_statuses[$status_name]->status_parent = '';
            }
        }

        $all_statuses['draft']->moderation = false;

        $status_by_position = [];

        // Draft status is always at position and order zero.
        // Note: order value is not stored, but derived from position value with consideration of other status properties.
        $this->addItemToArray($status_by_position, 0, $all_statuses['draft']);

        foreach ([false, true] as $disabled) {
            // Disabled statuses are postioned right after the Published / Private statuses in the management list, 
            // unavailable in status selection UI and not included in any status workflow auto-progression.
            if ($disabled && !$function_args['show_disabled']) {
                break;
            }

            // Classify the statuses based on stored position relative to core statuses
            foreach ($all_statuses as $key => $status) {
                // None of the customizations / default checks in this loop apply to the Draft status
                if ('draft' == $status->name) {
                    continue;
                }

                if ($status_is_disabled = !empty($status->disabled) || in_array($status->name, $disabled_statuses, true)) {
                    $status->disabled = true;

                    if ($status->position <= $all_statuses['_disabled']->position) {
                        $status->position = $all_statuses['_disabled']->position;
                    }
                }

                if (!$disabled && $status_is_disabled) {
                    continue;
                }
                
                if ($disabled && !$status_is_disabled) {
                    continue;
                }

                // Correct previous storage ambiguity
                if ('pending-review' === $status->name) {
                    $status->name = 'pending';
                    $status->slug = 'pending';
                }

                if (!in_array($status->slug, $core_statuses) && !in_array($status->slug, $pseudo_statuses)) {
                    if ($status->position >= $all_statuses['_disabled']->position) {
                        $status->disabled = true; // Fallback in case the disabled_statuses array is missing or out of sync (privacy statuses are pulled from a different taxonomy)
                    
                    } elseif (!empty($status->moderation)) { 
                        // Alternate workflow statuses will be displayed right before the Future and Published / Private statuses in the management list, 
                        // de-emphasized in status selection UI and not included in any status workflow auto-progression.
                        if ($status->position >= $all_statuses['_pre-publish-alternate']->position) {
                            $status->alternate = true;
                        
                        } elseif ($status->position >= $all_statuses['pending']->position) {
                            $status->post_pending = true;

                        } /*elseif ($status->position > $all_statuses['draft']->position) {

                        }
                        */
                    }
                }

                // Post type and status parent are not customizable for Pending status
                if ('pending' === $status->name) {
                    $status->status_parent = '';
                    $status->post_type = [];
                } else {
                    if (! isset($status->post_type)) {
                        $status->post_type = [];
                    }

                    if (! isset($status->status_parent)) {
                        $status->status_parent = '';
                    }
                }

                // Capability Status has never been set for this status
                if (! isset($status->capability_status)) {
                    $status->capability_status = '';
                }

                // Check if we need to set default colors and icons for current status
                if (! isset($status->color) || empty($status->color)) {
                    $status->color = self::DEFAULT_COLOR;
                }

                if (! isset($status->icon) || empty($status->icon)) {
                    $status->icon = self::DEFAULT_ICON;
                }

                // Allow spacing for previously unconfigured statuses to be inserted
                $disabled_offset = ($disabled) ? 100000 : 0;
                $this->addItemToArray($status_by_position, $disabled_offset + (int)$status->position * 1000, $status);
            }
        }

        // Sort the items numerically by position key
        ksort($status_by_position, SORT_NUMERIC);

        // Now set the order value (used for status selection UI and automation) based on stored position and other status properties

        $last_order =
        [   'pre_pending' => 0,             // 'draft' status is order 0, Pre-Pending workflow statuses allotted order 1 to 199
            'post_pending' => 200,          // 'pending' status is order 200, Post-Pending workflow statuses allotted order 201 to 299
            'alternate_workflow' => 299,    // Alternate Workflow statuses allotted order 300 to 699
            'private' => 800,               // 'private' status is order 800, Custom privacy statuses allotted order 801 to 899
            'disabled' => 999,              // Disabled statuses allotted order >= 1000
        ];

        foreach ($status_by_position as $position => $status) {
            if (in_array($status->slug, ['draft', 'pending', 'future', 'publish', 'private', '_pre-publish-alternate', '_disabled'])) {
                continue;
            }

            // Disabled statuses
            if (!empty($status->disabled)) {
                $last_order['disabled']++;
                $status_by_position[$position]->order = $last_order['disabled'];

            } elseif ($status->position > $all_statuses['private']->position) {
                $last_order['private']++;
                $status_by_position[$position]->order = $last_order['private'];
                
            } elseif ($status->position > $all_statuses['future']->position) {
                //possible future support for custom future statuses
                /*
                $last_order['future']++;
                $status->order = $last_order['future'];
                */
            
            // Alternate workflow statuses
            } elseif ($status->position >= $all_statuses['_pre-publish-alternate']->position) {
                $last_order['alternate_workflow']++;
                $status_by_position[$position]->order = $last_order['alternate_workflow'];
            
            } elseif ($status->position >= $all_statuses['pending']->position) {
                $last_order['post_pending']++;
                $status_by_position[$position]->order = $last_order['post_pending'];

            } elseif ($status->position > $all_statuses['draft']->position) {
                $last_order['pre_pending']++;
                $status_by_position[$position]->order = $last_order['pre_pending'];
            }
        }

        // @todo ?
        /*
        // If a plugin-defined moderation status has not been stored as a term, add it
        foreach($default_moderation_statuses as $status) {
            if (! term_exists($status->slug, 'post_status')) {
                //$status_term_args =   @todo
                $this->addStatus('post_status', $status->slug, $status_term_args);
            }
        }

        // If a plugin-defined visibility status has not been stored as a term, add it
        foreach($default_visibility_statuses as $status) {
            if (! term_exists($status->slug, 'post_visibility_pp')) {
                //$status_term_args =   @todo
                $this->addStatus('post_visibility_pp', $status->slug, $status_term_args);
            }
        }
        */

        global $wp_post_statuses;

        foreach (array_keys($status_by_position) as $key) {
            if (!$function_args['show_disabled']) {
                if (!empty($status_by_position[$key]->disabled)) {
                    unset($status_by_position[$key]);
                }
            }

            $status_by_position[$key] = $this->apply_default_status_properties($status_by_position[$key]);

            $status_name = $status_by_position[$key]->name;

            //if (!empty($args['mirror_to_wp_statuses'])) {
                foreach (get_object_vars($status_by_position[$key]) as $prop => $val) {
                    if (isset($wp_post_statuses[$status_name])) {
                        if (!isset($wp_post_statuses[$status_name]->$prop)) {
                            $wp_post_statuses[$status_name]->$prop = $val;
                        } else {
                            if ('labels' == $prop) {
                                $wp_post_statuses[$status_name]->$prop = $val;
                            }
                        }
                    }
                }
            //}
        }

        $this->custom_statuses_cache[$arg_hash] = $status_by_position;

        return $this->process_return_array($status_by_position, $status_args, $return_args, $function_args);
    }

    // Simple filtering / structuring applied to the statuses post-cache
    private function process_return_array($status_by_position, $status_args, $return_args, $function_args) {
        // $status_args: filtering of return array based on status properties
        //
        if (!is_array($status_args)) {
            $status_args = [];
        }

        // $return_args: return array structure
        //
        // support get_post_stati() argument structure
        if (is_string($return_args)) {
            $return_args = ['output' => $return_args];

        } elseif (!is_array($return_args)) {
            $return_args = [];
        }

        foreach (
            [
                'output' => 'names',
                'return_key' => 'name'
            ] 
        as $prop => $default_val) {
            if (!isset($return_args[$prop])) {
                $return_args[$prop] = $default_val;
            }
        }

        if (!self::isStatusManagement()) {
            foreach (array_keys($status_by_position) as $k) {
                if (in_array($status_by_position[$k]->slug, ['_pre-publish-alternate', '_disabled'])) {
                    unset ($status_by_position[$k]);
                }
            }
        }

        foreach (array_keys($status_args) as $prop) {
            if ('post_type' == $prop) {
                foreach ($status_by_position as $k => $status) {
                    if (!empty($status->post_type) && !array_intersect((array)$status_args['post_type'], (array)$status->post_type))
                        unset($status_by_position[$k]);
                }
            } else {
                foreach (array_keys($status_by_position) as $k) {
                    if ($status_args[$prop] && empty($status_by_position[$k]->$prop)) {
                        unset($status_by_position[$k]);

                    } elseif (!$status_args[$prop] && !empty($status_by_position[$k]->$prop)) {
                        unset($status_by_position[$k]);
                    }
                }
            }
        }

        $order = 0;

        foreach(array_keys($status_by_position) as $k) {
            $status_by_position[$k]->order = $order;
            $order++;
        }

        $return_arr = [];

        $return_key_order_val = !empty($return_args['return_key']) && ('order' == $return_args['return_key']);

        if (isset($return_args['output']) && in_array($return_args['output'], ['slug', 'name', 'names'])) {
            foreach (array_keys($status_by_position) as $key) {
                if ($return_key_order_val) {
                    $return_arr[] = $status_by_position[$key]->name;
                } else {
                    $return_arr[ $status_by_position[$key]->name ] = $status_by_position[$key]->name;
                }
            }

            return apply_filters('presspermit_get_post_statuses', $return_arr, $status_args, $return_args, $function_args);

        } elseif (isset($return_args['output']) && in_array($return_args['output'], ['label'])) {
            foreach (array_keys($status_by_position) as $key) {
                if ($return_key_order_val) {
                    $return_arr[] = $status_by_position[$key]->label;
                } else {
                    $return_arr[ $status_by_position[$key]->name ] = $status_by_position[$key]->label;
                }
            }

            return apply_filters('presspermit_get_post_statuses', $return_arr, $status_args, $return_args, $function_args);

        } elseif ($return_key_order_val) {
            return apply_filters('presspermit_get_post_statuses', $status_by_position, $status_args, $return_args, $function_args);
        }
    
        // While maintaining the same array ordering, return array keys will be changed to status name (slug) unless this function arg is set to 'order'
        foreach (array_keys($status_by_position) as $key) {
            $return_arr[ $status_by_position[$key]->name ] = $status_by_position[$key];
        }

        // Don't currently allow nested child statuses
        foreach ($return_arr as $key => $status) {
            if (!empty($status->status_parent)) {
                if (!empty($return_arr[ $status->status_parent ]->status_parent)) {
                    $return_arr[$key]->status_parent = '';
                }
            }
        }

        return apply_filters('presspermit_get_post_statuses', $return_arr, $status_args, $return_args, $function_args);
    }

    function flt_get_post_statuses($statuses, $status_args, $return_args, $function_args) {
        global $current_user;
        
        if (self::isContentAdministrator() || self::disable_custom_statuses_for_post_type()) { // || !get_option('cme_custom_status_control')) {
            return $statuses;
        }

        $context = (!empty($return_args['context'])) ? $return_args['context'] : '';

        // Maintain default PublishPress behavior (Permissions add-on / Capabilities Pro) for statuses that do not have custom capabilities enabled
        foreach ($statuses as $k => $obj) {
            $status_name = (is_object($obj)) ? $obj->name : $k;
            $_status = str_replace('-', '_', $status_name);

            if (!empty($obj->moderation) 
            && (!in_array($context, ['load', 'edit']))
            && !in_array($status_name, ['draft', 'future']) 
            && (!class_exists('PPS') || !PPS::postStatusHasCustomCaps($status_name))
            && empty($current_user->allcaps["status_change_{$_status}"])) {
                unset($statuses[$k]);
            }
        }
        
        return $statuses;
    }

    /**
     * Add item to Array without overwrite any item, in case an item is already set for the position.
     *
     * @param $array
     * @param $position
     * @param $item
     */
    private function addItemToArray(&$array, $position, $item)
    {
        if (isset($array[$position])) {
            $this->addItemToArray($array, $position + 1, $item);
        } else {
            $array[$position] = $item;
        }
    }

    public static function getStatusBy($field, $value) {
        return self::instance()->get_custom_status_by($field, $value);
    }

    public static function getCustomStatus($value) {
        return self::instance()->get_custom_status_by('name', $value);
    }

    /**
     * Returns a single status object
     *
     * @param string|int $string_or_int The status slug (name) or label to search for
     *
     * @return object|WP_Error|false $status
     */
    public function get_custom_status_by($field, $value)
    {
        if (! in_array($field, ['id', 'slug', 'name', 'label'])) {
            return false;
        }

        if (in_array($field, ['id', 'slug'])) {
            $field = 'name';
        }

        // New and auto-draft do not exist as status. So we map them to draft for now.
        if ('name' === $field && in_array($value, ['new', 'auto-draft'])) {
            $value = 'draft';
        }

        $custom_statuses = $this->getPostStatuses([], 'object', ['show_disabled' => self::isStatusManagement()]);

        $custom_status = wp_filter_object_list($custom_statuses, [$field => $value]);

        if (! empty($custom_status)) {
            return array_shift($custom_status);
        }

        return false;
    }

    // PublishPress Planner compat
    public static function get_link($args = [])
    {
        return self::getLink($args);
    }

    /**
     * Generate a link to one of the custom status actions
     *
     * @param array $args (optional) Action and any query args to add to the URL
     *
     * @return string $link Direct link to complete the action
     * @since 0.7
     *
     */
    public static function getLink($args = [])
    {
        if (! isset($args['action'])) {
            $args['action'] = '';
        }
        
        if (! isset($args['page'])) {
            $args['page'] = 'publishpress-statuses';
        }

        // Add other things we may need depending on the action
        switch ($args['action']) {
            case 'edit-status':
                $args['page'] = 'publishpress-statuses';
                $args['action'] = 'edit-status';

                if (!empty($args['slug'])) {
                    if (empty($args['name'])) {
                        $args['name'] = $args['slug'];
                    }
                }

                break;
            case 'delete-status':
                $args['_wpnonce'] = wp_create_nonce($args['action']);
                break;
            default:
                break;
        }

        return add_query_arg($args, get_admin_url(null, 'admin.php'));
    }


    /**
     * Encode all of the given arguments as a serialized array, and then base64_encode
     * Used to store extra data in a term's description field.
     *
     * @param array $args The arguments to encode
     *
     * @return string Arguments encoded in base64
     *
     */
    public static function get_encoded_description($args = [])
    {
        return base64_encode(maybe_serialize($args));
    }

    /**
     * If given an encoded string from a term's description field,
     * return an array of values. Otherwise, return the original string
     *
     * @param string $string_to_unencode Possibly encoded string
     *
     * @return array Array if string was encoded, otherwise the string as the 'description' field
     *
     */
    public static function get_unencoded_description($string_to_unencode)
    {
        return maybe_unserialize(base64_decode($string_to_unencode));
    }

    /**
     * Adds a new custom status as a term in the wp_terms table.
     * Basically a wrapper for the wp_insert_term class.
     *
     * The arguments decide how the term is handled based on the $args parameter.
     * The following is a list of the available overrides and the defaults.
     *
     * 'description'. There is no default. If exists, will be added to the database
     * along with the term. Expected to be a string.
     *
     * 'slug'. Expected to be a string. There is no default.
     *
     * @param int|string $term The status to add or update
     * @param array|string $args Change the values of the inserted term
     *
     * @return array|WP_Error $response The Term ID and Term Taxonomy ID
     */
    public function addStatus($taxonomy, $name, $args = [])
    {
        $slug = (! empty($args['slug'])) ? $args['slug'] : sanitize_title($name);
        unset($args['slug']);
        $encoded_description = self::get_encoded_description($args);

        $response = wp_insert_term(
            $name,
            $taxonomy,
            ['slug' => $slug, 'description' => $encoded_description]
        );

        // Reset our internal object cache
        $this->custom_statuses_cache = [];

        if ('post_status' == $taxonomy) {   // @todo: review implementation for visibility statuses
            // Set permissions for the base roles
            $roles = ['administrator', 'editor', 'author', 'contributor'];
            foreach ($roles as $roleSlug) {
                $role = get_role($roleSlug);
                if (! empty($role)) {
                    $role->add_cap('status_change_' . str_replace('-', '_', $slug));
                }
            }
        }

        return $response;
    }

    /**
     * Get array of all supported post type slugs
     */
    public function get_supported_post_types()
    {
        $postTypes = get_post_types(
            [
                'show_ui'  => true,
            ], 
            'objects'
        );

        $postTypes = array_diff_key($postTypes, array_fill_keys(['attachment', 'wp_block', 'wp_navigation'], true));

        $postTypes = apply_filters('publishpress_statuses_supported_post_types', $postTypes);

        // Hide notification workflows from the list
        if (isset($postTypes['psppnotif_workflow'])) {
            unset($postTypes['psppnotif_workflow']);
        }

        return $postTypes;
    }


    // @todo: move to an admin module

    /**
     * Generate an option field to turn post type support on/off for a given module
     *
     * @param array  $post_types  If empty, we consider all post types
     *
     */
    public function helper_option_custom_post_type($post_types = [])
    {
        if (empty($this->module)) {
            return;
        }

        if (empty($post_types)) {
            $post_types = [
                'post' => __('Posts'),
                'page' => __('Pages'),
            ];

            $custom_post_types = $this->get_supported_post_types();

            foreach ($custom_post_types as $custom_post_type => $args) {
                $post_types[$custom_post_type] = $args->label;
            }
        }

        echo '<div class="pp-statuses-post-types">';

        foreach ($post_types as $post_type => $title) {
            echo '<label for="' . esc_attr($post_type) . '-' . $this->module->slug . '">';
            echo '<input id="' . esc_attr($post_type) . '-' . $this->module->slug . '" name="'
                . $this->options_group_name . '[post_types][' . esc_attr($post_type) . ']"';
            
            if (isset($this->options->post_types[$post_type])) {
                //checked($this->options->post_types[$post_type], 'on');
                checked($this->options->post_types[$post_type], true);
            }

            // Defining post_type_supports in the functions.php file or similar should disable the checkbox
            disabled(post_type_supports($post_type, $this->post_type_support_slug), true);
            echo ' type="checkbox" value="on" />&nbsp;&nbsp;&nbsp;' . esc_html($title) . '</label>';
            
            // Leave a note to the admin as a reminder that add_post_type_support has been used somewhere in their code
            if (post_type_supports($post_type, $this->post_type_support_slug)) {
                echo '&nbsp&nbsp;&nbsp;<span class="description">' . sprintf(__('Disabled because add_post_type_support(\'%1$s\', \'%2$s\') is included in a loaded file.', 'publishpress-statuses'), $post_type, $this->post_type_support_slug) . '</span>';
            }
        }

        echo '</div>';
    }

    public static function orderStatuses($statuses = false, $args = [])
    {
        if (false === $statuses) {
            global $wp_post_statuses;
            $statuses = $wp_post_statuses;
        }

        if (self::disable_custom_statuses_for_post_type()) {
            return $statuses;
        }

        $defaults = [
            'min_order' => 0, 
            'status_parent' => false, 
            'omit_status' => [], 
            'include_status' => [], 
            'whitelist_status' => [], 
            'require_order' => false
        ];

        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        // convert integer keys to slugs
        foreach ($statuses as $status => $obj) {
            if (is_numeric($status)) {
                $statuses[$obj->name] = $obj;
                unset($statuses[$status]);
            }
        }

        $include_status = (array)$include_status;
        $whitelist_status = (array)$whitelist_status;

        if (!empty($omit_status)) {
            $statuses = array_diff_key(
                $statuses, 
                array_fill_keys(array_diff((array)$omit_status, $include_status), true)
            );
        }

        if (!empty($min_order)) {
            foreach ($statuses as $status => $status_obj) {
                if (!in_array($status, $include_status, true) 
                && (empty($status_obj->position) || ($status_obj->position < $min_order))
                ) {
                    unset($statuses[$status]);
                }
            }
        }

        foreach ($statuses as $status => $status_obj) {
            if ($require_order && empty($status_obj->position)) {
                unset($statuses[$status]);
                continue;
            }

            if ((false !== $status_parent) && !in_array($status, $whitelist_status)) {
                $_parent = (isset($status_obj->status_parent)) ? $status_obj->status_parent : '';

                if (($_parent != $status_parent) && ($require_order || ($status != $status_parent))) {
                    unset($statuses[$status]);
                    continue;
                }
            }
        }

        return $statuses;
    }

    public static function getNextStatusObject($post_id = 0, $args = [])
    {
        global $wp_post_statuses;

        $defaults = ['moderation_statuses' => [], 'can_set_status' => [], 'force_main_channel' => false, 'post_type' => '', 'post_status' => '', 'default_by_sequence' => null, 'skip_post_id_check' => false, 'skip_current_status_check' => false];

        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $post_type = sanitize_key($post_type);

        if (is_object($post_id)) {
            $post = $post_id;
            $post_id = $post->ID;
        } else {
            if (!$post_id && !$skip_post_id_check) {
                $post_id = \PublishPress_Functions::getPostID();
            }

            if ($post_id) {
                $post = get_post($post_id);
            } else {
                $post = false;
            }
        }

        if (empty($post)) {
            $post_status = 'draft';
            $post_type = (!empty($args['post_type'])) ? $args['post_type'] : \PublishPress_Functions::findPostType();
        } else {
            $post_type = $post->post_type;
            $post_status = $post->post_status;
        }

        if (!empty($args['post_status'])) {
            $post_status = $args['post_status'];
        }

        if (!$post_status_obj = get_post_status_object($post_status)) {
            $post_status_obj = get_post_status_object('draft');
        }

        $is_administrator = self::isContentAdministrator();
        if (!$type_obj = get_post_type_object($post_type)) {
            return $post_status_obj;
        }

        if (empty($moderation_statuses)) {
            $moderation_statuses = \PublishPress_Statuses::getPostStati(['moderation' => true], 'object');
        }

        if (empty($post_status_obj->alternate)) {
            foreach ($moderation_statuses as $k => $_status) {
                if (!empty($_status->alternate)) {
                    unset($moderation_statuses[$k]);
                }
            }
        } else {
            foreach ($moderation_statuses as $k => $_status) {
                if ((empty($_status->alternate) 
                || ($post_status_obj->status_parent && ($_status->status_parent != $post_status_obj->status_parent)))
                && !in_array($k, ['draft', 'publish'])
                ) {
                    unset($moderation_statuses[$k]);
                }
            }
        }

        if (empty($can_set_status)) {
            $can_set_status = self::getUserStatusPermissions('set_status', $type_obj->name, $moderation_statuses);
        }

        if ('auto-draft' == $post_status)
            $post_status = 'draft';

        if (!empty($post_status_obj->public) || !empty($post_status_obj->private) || ('future' == $post_status_obj->name)) {
            if (!$skip_current_status_check) {
                return $post_status_obj;
            }
        }

        if (is_null($default_by_sequence)) {
            $default_by_sequence = \PublishPress_Statuses::instance()->options->moderation_statuses_default_by_sequence;
        }

        if (current_user_can($type_obj->cap->publish_posts) 
        && (!$default_by_sequence || apply_filters('presspermit_editor_default_publish', false, $post))
        ) {
            if (!empty($post) && !empty($post->post_date_gmt) && time() < strtotime($post->post_date_gmt . ' +0000')) {
                return get_post_status_object('future');
            } else {
                return get_post_status_object('publish');
            }
        } else {
            if (empty($moderation_statuses)) {
                $moderation_statuses = \PublishPress_Statuses::getPostStati(
                    ['moderation' => true, 'internal' => false, 'post_type' => $type_obj->name]
                    , 'object'
                );
                
                unset($moderation_statuses['future']);
            }

            // Don't default to another moderation status of equal or lower order
            $status_order = (!empty($post_status_obj->position)) ? $post_status_obj->position : 0;
            $_args = ['min_order' => $status_order + 1, 'omit_status' => 'future', 'require_order' => true];

            if (!$force_main_channel) {
                if (!empty($post_status_obj->status_parent)) {
                    // If current status is a Workflow branch child, only offer other statuses in that branch
                    $_args['status_parent'] = $post_status_obj->status_parent;

                } elseif ($status_children = self::getStatusChildren($post_status_obj->name, $moderation_statuses)) {
                    if ($default_by_sequence) {
                        // If current status is a Workflow branch parent, only offer other statuses in that branch
                        $_args['status_parent'] = $post_status_obj->name;
                        unset($_args['min_order']);
                        $moderation_statuses = $status_children;
                    } else {
                        $_args['status_parent'] = '';
                    }
                } else {
                    $_args['status_parent'] = '';  // don't default from a main channel into a branch status
                }
            }

            $_post = (!empty($post)) ? $post : get_post($post_id);

            if (!$_moderation_statuses = self::orderStatuses($moderation_statuses, $_args)) {
                // If there are no more statuses in a branch, return next status outside branch
                unset($_args['status_parent']);
                $_moderation_statuses = self::orderStatuses($moderation_statuses, $_args);
            }

            $moderation_statuses = apply_filters(
                'presspermit_editpost_next_status_priority_order', 
                $_moderation_statuses, 
                ['post' => $_post]
            );

            // If this user cannot set any further progression steps, return current post status
            if (!$moderation_statuses) {
                if ((!empty($post_status_obj->status_parent) || !empty($status_children)) && !$force_main_channel) {
                    $args['force_main_channel'] = true;
                    return self::getNextStatusObject($post_id, $args);
                }
            } else {
                if (!$default_by_sequence || (defined('PRESSPERMIT_CHILD_STATUSES_ALWAYS_SEQUENCED') && (!empty($status_children) || !empty($post_status_obj->status_parent)))) {

                    // Defaulting to highest order that can be set by the user...
                    $moderation_statuses = array_reverse($moderation_statuses);
                }

                foreach ($moderation_statuses as $_status_obj) {
                    if (!empty($can_set_status[$_status_obj->name]) && ($_status_obj->name != $post_status_obj->name)) {
                        $post_status_obj = $_status_obj;
                        break;
                    }
                }
            }

            // If logic somehow failed, default to draft
            if (empty($post_status_obj)) {
                if (defined('PP_LEGACY_PENDING_STATUS') || !empty($can_set_status['pending'])) {
                    $post_status_obj = get_post_status_object('pending');
                } else {
                    $post_status_obj = get_post_status_object('draft');
                }
            }

            $override_status = apply_filters(
                'presspermit_workflow_progression', 
                $post_status_obj->name, 
                $post_id, 
                compact('moderation_statuses')
            );

            if (($override_status != $post_status_obj->name) 
            && $can_set_status[$override_status]
            ) {
                $post_status_obj = get_post_status_object($override_status);
            }

            if (($post_status_obj->name == $post_status) && current_user_can($type_obj->cap->publish_posts)) {
                $post_status_obj = get_post_status_object('publish');
            }
        }

        if (empty($post_status_obj) || ('auto-draft' == $post_status_obj->name)) {
            return get_post_status_object('draft');
        }

        return $post_status_obj;
    }

    public static function defaultStatusProgression($post_id = 0, $args = [])
    {
        $defaults = ['return' => 'object', 'moderation_statuses' => [], 'can_set_status' => [], 'force_main_channel' => false, 'post_type' => '', 'default_by_sequence' => null, 'skip_current_status_check' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $post_type = sanitize_key($post_type);

        if (!$status_obj = self::getNextStatusObject($post_id, $args)) {
            $status_obj = get_post_status_object('draft');
        }

        return ('name' == $return) ? $status_obj->name : $status_obj;
    }

    public static function getStatusChildren($status, $statuses = false)
    {
        if (!get_post_status_object($status)) {
            return [];
        }

        if (false === $statuses) {
            $statuses = self::getPostStati(['internal' => false], 'object');
        } else {
            $statuses = (array)$statuses;
        }

        $return = [];

        foreach ($statuses as $other_status_obj) {
            if (!empty($other_status_obj->status_parent) && ($status == $other_status_obj->status_parent)) {
                $key = (isset($other_status_obj->slug)) ? $other_status_obj->slug : strtolower($other_status_obj->name);
                $return [$key] = $other_status_obj;
            }
        }

        return $return;
    }

    public static function haveStatusPermission($perm_name, $post_type, $post_status, $args = [])
    {
        $perms = self::getUserStatusPermissions($perm_name, $post_type, $post_status, $args);
        return !empty($perms[$post_status]);
    }

    public static function getUserStatusPermissions($perm_name, $post_type, $check_statuses, $args = [])
    {
        global $wp_post_statuses, $current_user;

        if (class_exists('PublishPress\Permissions\Statuses')) {
            return \PublishPress\Permissions\Statuses::getUserStatusPermissions($perm_name, $post_type, $check_statuses, $args);

        } elseif ('set_status' != $perm_name) {
            return [];
        }

        $check_statuses = (array) $check_statuses;

        $elem = reset($check_statuses);
        if (is_object($elem)) {
            $check_statuses = array_fill_keys(array_keys($check_statuses), true);
        } else {
            $check_statuses = array_fill_keys((array)$check_statuses, true);
        }

        if (\PublishPress_Statuses::isContentAdministrator()) {
            // This function will probably not get called for Administrator, but return valid response if it is.
            return $check_statuses;
        }

        // perm_name 'set_status'
        if (!$type_obj = get_post_type_object($post_type)) {
            return [];
        }

        $return = [];

        if (!isset($type_obj->cap->set_posts_status)) {
            $type_obj->cap->set_posts_status = $type_obj->cap->publish_posts;
        }

        $moderate_any = !empty($current_user->allcaps['pp_moderate_any']);

        foreach (array_keys($check_statuses) as $_status) {
            if ($moderate_any && !empty($wp_post_statuses[$_status]) 
            && !empty($wp_post_statuses[$_status]->moderation)
            ) {
                // The pp_moderate_any capability allows a non-Administrator to set any moderation status
                $return[$_status] = true;
                continue;
            }
            
            $status_change_cap = str_replace('-', '_', "status_change_{$_status}");
            $check_caps = (in_array($_status, ['publish', 'future'])) ? [$type_obj->cap->publish_posts] : [$status_change_cap];

            $check_caps = apply_filters('publishpress_statuses_required_caps', $check_caps, 'set_status', $_status, $post_type);

            $return[$_status] = !array_diff($check_caps, array_keys($current_user->allcaps));
        }

        return $return;
    }

    public static function filterAvailablePostStatuses($statuses, $post_type, $post_status)
    {
        if (!$post_type) {
            return $statuses;
        }

        // convert integer keys to slugs
        foreach ($statuses as $status => $obj) {
            if (is_numeric($status)) {
                $statuses[$obj->name] = $obj;
                unset($statuses[$status]);
            }

            if (!empty($obj->post_type) && !in_array($post_type, $obj->post_type)) {
                unset($statuses[$obj->name]);
            }
        }

        $can_set_status = self::getUserStatusPermissions('set_status', $post_type, $statuses);

        $can_set_status[$post_status] = true;

        return array_intersect_key($statuses, array_filter($can_set_status));
    }

    public static function isContentAdministrator() {
        return current_user_can('administrator') || current_user_can('pp_administer_content') || (is_multisite() && is_super_admin());
    }

    public static function updateStatusNumRoles($check_status_name, $args = []) {
        if (!$status_num_roles = get_option('publishpress_statuses_num_roles')) {
            $status_num_roles = [];
        }

        if (empty($args['force_refresh']) && !empty($status_num_roles[$check_status_name])) {
            return $status_num_roles[$check_status_name];
        }

        $statuses = [$check_status_name];

        if (!$status_num_roles) {
            $statuses = self::getPostStati(['moderation' => true], 'names');
            

            $statuses = array_diff($statuses, ['future']);
            $statuses = array_unique(array_merge($statuses, [$check_status_name]));
        }

        $role_caps = [];
        $roles = \PublishPress_Functions::getRoles(true);

        foreach (array_keys($roles) as $role_name) {
            if ($role = get_role($role_name)) {
                $role_caps[$role_name] = $role->capabilities;
            }
        }

        foreach ($statuses as $status_name) {
            $cap_name = str_replace('-', '_', "status_change_{$status_name}");
            $admin_cap_name = 'manage_options';

            $status_num_roles[$status_name] = 0;

            foreach ($role_caps as $caps) {
                if (!empty($caps[$cap_name]) || !empty($caps[$admin_cap_name]) || !empty($caps['administrator'])) {
                    $status_num_roles[$status_name]++;
                }
            }
        }

        update_option('publishpress_statuses_num_roles', $status_num_roles);

        return (isset($status_num_roles[$check_status_name])) ? $status_num_roles[$check_status_name] : 0;
    }

    public function fltPostStatus($post_status)
    {
        global $current_user;

        $post_id = \PublishPress_Functions::getPostID();

        if ($_post = get_post($post_id)) {
            $type_obj = get_post_type_object($_post->post_type);
        }

        if ('_pending' == $post_status) {
            $save_as_pending = true;
            $post_status = 'pending';
            
        } elseif (('pending' == $post_status) && !empty($type_obj) && current_user_can($type_obj->cap->publish_posts)) {
            $save_as_pending = true;
        }

        $status_obj = get_post_status_object($post_status);

        $is_administrator = \PublishPress_Statuses::isContentAdministrator();

        if ($stored_status = get_post_field('post_status', $post_id)) {
            $stored_status_obj = get_post_status_object($stored_status);
        }

        $_post_status = (!empty($_POST) && !empty($_POST['post_status'])) ? $_POST['post_status'] : '';

        $selected_status = ($_post_status && ('publish' != $_post_status)) ? $_post_status : $post_status;

        if ('public' == $selected_status) {
            $selected_status = 'publish';
        }

        if (!$post_status_obj = get_post_status_object($selected_status)) {
            return $post_status;
        }

        // Important: if other plugin code inserts additional posts in response, don't filter those
        static $done;
        if (!empty($done)) return $post_status;  
        $done = true;

        $post_status = $selected_status;

        $_post = get_post($post_id);

        if (empty($_post)) {
            return $post_status;
        }

        $doing_rest = defined('REST_REQUEST') && (!empty($_REQUEST['meta-box-loader']) || $this->doing_rest);

        // Allow Publish / Submit button to trigger our desired workflow progression instead of Publish / Pending status.
        // Apply this change only if stored post is not already published or scheduled.
        // Also skip retain normal WP editor behavior if the newly posted status is privately published or future.
        if (
            (in_array($selected_status, ['publish', 'pending', 'future']) && !in_array($stored_status, ['publish', 'private', 'future']) 
            && empty($stored_status_obj->public) && empty($stored_status_obj->private))
            //(!$doing_rest && ('publish' == $selected_status))
        ) {
            // Gutenberg REST gives no way to distinguish between Publish and Save request. Treat as Publish (next workflow progression) if any of the following:
            //  * user cannot set pending status
            //  * already set to pending status

            if (
                empty($save_as_pending) /* Pending status was not explicitly selected by dropdown / checkbox */
                || ! defined('REST_REQUEST')
                || ! $doing_rest
                || (('publish' == $selected_status) && !\PublishPress_Statuses::haveStatusPermission('set_status', $_post->post_type, 'pending')) 
            ) {
                // Users who have publish capability do not need defaultStatusProgression() functionality, so do not get the status="_pending" dropdown option to indicate an explicit Save as Pending request
                if ('pending' == $selected_status) {
                    $type_obj = get_post_type_object($_post->post_type);
                    $can_publish = ($type_obj) ? !empty($current_user->allcaps[$type_obj->cap->publish_posts]) : false;
                }

                require_once(__DIR__ . '/REST.php');
                $rest = \PublishPress_Statuses\REST::instance();
                $workflow_action = isset($rest->params['pp_workflow_action']) ? $rest->params['pp_workflow_action'] : false;

                $selected_status_dropdown = isset($rest->params['pp_status_selection']) ? $rest->params['pp_status_selection'] : $selected_status;

                $post_type = ($post_id) ? '' : \PublishPress_Functions::findPostType();

                switch ($workflow_action) {
                    case 'specified':
                        $post_status = $selected_status_dropdown;
                        break;

                    case 'current':
                        $post_status = $stored_status;
                        break;

                    case 'next':
                        $post_status = \PublishPress_Statuses::defaultStatusProgression($post_id, ['default_by_sequence' => true, 'return' => 'name', 'post_type' => $post_type]);
                        break;

                    case 'max':
                        $post_status = \PublishPress_Statuses::defaultStatusProgression($post_id, ['default_by_sequence' => false, 'return' => 'name', 'post_type' => $post_type]);
                        break;

                    default:
                        if (empty($save_as_pending) && ($doing_rest && ($selected_status != $stored_status || (('pending' == $selected_status) && !$can_publish))) || (!empty($_POST) && !empty($_POST['publish']))) { //} && ! empty( $_POST['pp_submission_status'] ) ) { 
                            // Submission status inferred using same logic as UI generation (including permission check)
                            $post_status = \PublishPress_Statuses::defaultStatusProgression($post_id, ['return' => 'name', 'post_type' => $post_type]);
                        }
                }

                $filtered_status = apply_filters('presspermit_selected_moderation_status', $post_status, $post_id);

                if (($filtered_status != $post_status) 
                && \PublishPress_Statuses::haveStatusPermission('set_status', $_post->post_type, $filtered_status)
                ) {
                    $post_status = $filtered_status;
                }
            }

            // Final permission check to cover all other custom statuses (draft, publish and private status capabilities are already checked by WP)
        } elseif (!empty($_post) && !$is_administrator && ($post_status != $stored_status) 
        && !in_array($post_status, ['draft', 'publish', 'private']) 
        && !\PublishPress_Statuses::haveStatusPermission('set_status', $_post->post_type, $post_status)
        ) {
            
            $post_status = ($stored_status) ? $stored_status : 'draft';
        }

        return $post_status;
    }

    // log request and handler parameters for possible reference by subsequent PP filters; block unpermitted create/edit/delete requests 
    function fltRestPreDispatch($rest_response, $rest_server, $request)
    {
        $this->doing_rest = true;

        require_once(__DIR__ . '/REST.php');
        return \PublishPress_Statuses\REST::instance()->pre_dispatch($rest_response, $rest_server, $request);
    }

    function actRestInit()
    {
        register_post_status(
            '_pending', 
            [
                'label'                     => esc_html__('Pending'),
                'label_count'               => false,
                'exclude_from_search'       => true,
                'public'                    => false,
                'internal'                  => false,
                'protected'                 => true,
                'private'                   => false,
                'publicly_queryable'        => false,
                'show_in_admin_status_list' => false,
                'show_in_admin_all_list'    => false,
            ]
        );

        foreach(get_post_types(['public' => true, 'show_ui' => true], 'names', 'or') as $post_type) {
            register_rest_field( $post_type, 'pp_workflow_action', array(
                'get_callback' => [__CLASS__, 'getWorkflowAction'],
                'update_callback' => [__CLASS__, 'updateWorkflowAction'],
                'schema' => [
                    'description'   => 'Workflow Action',
                    'type'          => 'string',
                    'context'       =>  ['view','edit']
                    ]
                )
            );

            register_rest_field( $post_type, 'pp_status_selection', array(
                'get_callback' => [__CLASS__, 'getStatusSelection'],
                'update_callback' => [__CLASS__, 'updateStatusSelection'],
                'schema' => [
                    'description'   => 'StatusSelection',
                    'type'          => 'string',
                    'context'       =>  ['view','edit']
                    ]
                )
            );
        }
    }

    public static function getWorkflowAction( $object ) {
        $default_action = '';
        $post_status = '';

        if ($post_id = \PublishPress_Functions::getPostID()) {
            if ($post_status = get_post_field('post_status', $post_id)) {
                if ($status_obj = get_post_status_object($post_status)) {
                    if (in_array($post_status, ['publish', 'private', 'future']) || !empty($stored_status_obj->public) || !empty($stored_status_obj->private)) {
                        $default_action = 'current';
                    }
                }
            }

            $post_type = get_post_field('post_type', $post_id);
        } else {
            $post_type = \PublishPress_Functions::findPostType();
        }

        if (!$default_action) {
            $default_status = \PublishPress_Statuses::defaultStatusProgression($post_id, ['return' => 'name', 'post_type' => $post_type]);

            if ($default_status != $post_status) {
                if (\PublishPress_Statuses::instance()->options->moderation_statuses_default_by_sequence) {
                    $default_action = 'next';
                } else {
                    $default_action = 'max';
                }
            }
        }
        
        return $default_action;
    }

    public static function updateWorkflowAction( $value, $object ) {
        /*
        $id = (is_object($object)) ? $object->ID : $object['id'];
        */

        return false;
    }

    public static function getStatusSelection( $object ) {
        $status_selection = '';

        if ($post_id = \PublishPress_Functions::getPostID()) {
            if ($post_status = get_post_field('post_status', $post_id)) {
                if (get_post_status_object($post_status)) {
                    $status_selection = $post_status;
                }
            }
        }

        return $status_selection;
    }

    public static function updateStatusSelection( $value, $object ) {
        /*
        $id = (is_object($object)) ? $object->ID : $object['id'];
        */

        return false;
    }
}
}