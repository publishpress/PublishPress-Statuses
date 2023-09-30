<?php
namespace PublishPress_Statuses;

// Custom Status admin menus, shared javascript and CSS
class Admin
{
    function __construct() {
        add_action('admin_menu', [$this, 'act_admin_menu'], 21);

        // Load CSS and JS resources that we probably need
        add_action('admin_print_styles', [$this, 'add_admin_styles']);
        add_action('admin_enqueue_scripts', [$this, 'action_admin_enqueue_scripts']);
    }

    function add_admin_styles() {
        $plugin_page = \PublishPress_Functions::getPluginPage();

        if (0 === strpos($plugin_page, 'publishpress-statuses')) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            wp_enqueue_style(
                'publishpress-status-admin-css',
                PUBLISHPRESS_STATUSES_URL . 'common/css/custom-status-admin.css',
                [],
                PUBLISHPRESS_STATUSES_VERSION
            );

            wp_enqueue_style('presspermit-admin-common', PUBLISHPRESS_STATUSES_URL . '/common/libs/publishpress/publishpress-admin.css', [], PUBLISHPRESS_STATUSES_VERSION);
        }
    }

    /**
     * Enqueue Javascript resources that we need in the admin:
     * - Primary use of Javascript is to manipulate the post status dropdown on Edit Post and Manage Posts
     * - jQuery Sortable plugin is used for drag and dropping custom statuses
     * - We have other custom code for JS niceties
     */
    public function action_admin_enqueue_scripts()
    {
        global $pagenow;

        if (\PublishPress_Statuses::DisabledForPostType()) {
            return;
        }

        $plugin_page = \PublishPress_Functions::getPluginPage();

        // Scripts and styles needed for Add Status, Edit Status, and possibly Statuses
        if (0 === strpos($plugin_page, 'publishpress-statuses')) {
            wp_enqueue_script(
                'publishpress-icon-preview',
                PUBLISHPRESS_STATUSES_URL . 'common/libs/icon-picker/icon-picker.js',
                ['jquery'],
                PUBLISHPRESS_STATUSES_VERSION,
                true
            );
            wp_enqueue_style(
                'publishpress-icon-preview',
                PUBLISHPRESS_STATUSES_URL . 'common/libs/icon-picker/icon-picker.css',
                ['dashicons'],
                PUBLISHPRESS_STATUSES_VERSION,
                'all'
            );

            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

            wp_enqueue_script(
                'publishpress-status-edit',
                PUBLISHPRESS_STATUSES_URL . "common/js/status-edit{$suffix}.js",
                ['jquery', 'jquery-ui-sortable'],
                PUBLISHPRESS_STATUSES_VERSION,
                true
            );
        }

        // Scripts and styles for Statuses screen
        if ('publishpress-statuses' == $plugin_page
        && in_array(\PublishPress_Functions::REQUEST_key('action'), ['', 'statuses'])
        ) {
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-datepicker');

            global $wp_post_statuses;

            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

            wp_enqueue_script(
                'ui-touch-punch', 
                PUBLISHPRESS_STATUSES_URL . 'common/libs/jquery.ui.touch-punch/jquery.ui.touch-punch.min.js', 
                ['jquery', 'jquery-ui-sortable'], 
                PUBLISHPRESS_STATUSES_VERSION
            );
            wp_enqueue_script(
                'nested-sortable-mjs-pp', 
                PUBLISHPRESS_STATUSES_URL . 'common/libs/jquery.mjs.nestedSortable-pp/jquery.mjs.nestedSortable-pp.js', 
                ['jquery', 'jquery-ui-sortable'], 
                PUBLISHPRESS_STATUSES_VERSION
            );

            wp_enqueue_script(
                'publishpress-custom-status-configure',
                PUBLISHPRESS_STATUSES_URL . "common/js/custom-status-configure{$suffix}.js",
                ['jquery', 'jquery-ui-sortable'],
                PUBLISHPRESS_STATUSES_VERSION,
                true
            );

            wp_localize_script(
                'publishpress-custom-status-configure',
                'objectL10ncustomstatus',
                [
                    'pp_confirm_delete_status_string' => __(
                        'Are you sure you want to delete the post status? All posts with this status will be assigned to the default status.',
                        'publishpress-statuses'
                    ),
                ]
            );
        }

        // Custom javascript to modify the post status dropdown where it shows up
        if (self::is_post_management_page()) {
            if (class_exists('PublishPress_Functions')) { // @todo: refine library dependency handling
                if (\PublishPress_Functions::isBlockEditorActive()) {
                    wp_enqueue_style(
                        'publishpress-custom_status-block',
                        PUBLISHPRESS_STATUSES_URL . 'common/css/custom-status-block-editor.css',
                        false,
                        PUBLISHPRESS_STATUSES_VERSION,
                        'all'
                    );
                } else {
                    wp_enqueue_style(
                        'publishpress-custom_status-classic',
                        PUBLISHPRESS_STATUSES_URL . 'common/css/custom-status-classic-editor.css',
                        false,
                        PUBLISHPRESS_STATUSES_VERSION,
                        'all'
                    );
                }
            }
        }
    }

    /**
     * Primary configuration page for custom status class.
     * Shows form to add new custom statuses on the left and a
     * WP_List_Table with the custom status terms on the right
     */
    public function render_admin_page()
    {
        require_once(__DIR__ . '/StatusesUI.php');
        $ui = \PublishPress_Statuses\StatusesUI::instance();
        $ui->render_admin_page();
    }

    function act_admin_menu()
    {
        $this->menu_slug = 'publishpress-statuses';

        $this->using_permissions_menu = true;

        $check_cap = (current_user_can('manage_options')) ? 'read' : 'pp_manage_statuses';

        add_menu_page(
            esc_html__('Statuses', 'publishpress-statuses'),
            esc_html__('Statuses', 'publishpress-statuses'),
            $check_cap,
            'publishpress-statuses',
            [$this, 'render_admin_page'],
            'dashicons-format-status',
            70
        );

        add_submenu_page(
            'publishpress-statuses',
            esc_html__('Add New', 'publishpress-statuses'), 
            esc_html__('Add New', 'publishpress-statuses'), 
            $check_cap,
            'publishpress-statuses-add-new', 
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'publishpress-statuses',
            esc_html__('Settings', 'publishpress-statuses'), 
            esc_html__('Settings', 'publishpress-statuses'), 
            'manage_options',   // @todo: custom capability?
            'publishpress-statuses-settings', 
            [$this, 'render_admin_page']
        );
    }

    /**
     * Check whether custom status stuff should be loaded on this page
     *
     * @todo migrate this to the base module class
     */
    public static function is_post_management_page()
    {
        global $pagenow;

        if (! in_array(\PublishPress_Statuses::getCurrentPostType(), \PublishPress_Statuses::getEnabledPostTypes())) {
            return false;
        }

        $post_type_obj = get_post_type_object(\PublishPress_Statuses::getCurrentPostType());

        if (! current_user_can($post_type_obj->cap->edit_posts)) {
            return false;
        }

        // Disable the scripts for the post page if the plugin Visual Composer is enabled.
        if ('frontend' === \PublishPress_Functions::GET_key('vcv-action')) {
            return false;
        }

        // Only add the script to Edit Post and Edit Page pages -- don't want to bog down the rest of the admin with unnecessary javascript
        return in_array(
            $pagenow,
            ['post.php', 'edit.php', 'post-new.php', 'page.php', 'edit-pages.php', 'page-new.php']
        );
    }

    // @todo: merge into getPostStatuses() / register_post_status() calls

    public static function set_status_labels($status)
    {
        foreach (['icon', 'color'] as $prop) {
            if (empty($status->$prop)) {
                $status->$prop = '';
            }
        }

        if (empty($status->label)) {
            $status->label = ucwords($status->name);
        }

        if (empty($status->labels)) {
            $status->labels = (object) [];
        }

        if (!isset($status->labels->name)) {
            $status->labels->name = $status->label;
        }

        if (!isset($status->labels->caption)) {
            $status->labels->caption = $status->labels->name;
        }

        if (empty($status->label_count)) {
            // translators: %s: post count
            $sing = sprintf(__('%s <span class="count">()</span>', 'publishpress-statuses'), $status->label);
            $plur = sprintf(__('%s <span class="count">()</span>', 'publishpress-statuses'), $status->label);

            $status->label_count = _n_noop(
                str_replace('()', '(%s)', $sing), 
                str_replace('()', '(%s)', $plur)
            );
        }

        if (empty($status->labels->publish)) {
            // @todo: redundant with status definition?
            if ('pending' == $status->name) {
                $status->labels->publish = esc_html__('Submit for Review', 'publishpress-statuses');
            } elseif ('approved' == $status->name) {
                $status->labels->publish = esc_html__('Approve', 'publishpress-statuses');
            } elseif ('assigned' == $status->name) {
                $status->labels->publish = esc_html__('Assign', 'publishpress-statuses');
            } elseif ('in-progress' == $status->name) {
                $status->labels->publish = esc_html__('Mark In Progress', 'publishpress-statuses');
            } elseif ('publish' == $status->name) {
                $status->labels->publish = esc_html__('Publish', 'publishpress-statuses');
            } elseif ('future' == $status->name) {
                $status->labels->publish = esc_html__('Schedule', 'publishpress-statuses');
            } else {
                if (strlen($status->label) > 16) {
                    $status->labels->publish = __('Submit', 'publishpress-statuses');
                } elseif (strlen($status->label) > 13) {
                    // translators: %s: post status
                    $status->labels->publish = esc_attr(sprintf(__('Set to %s', 'publishpress-statuses'), $status->label));
                } else {
                    // translators: %s: post status
                    $status->labels->publish = esc_attr(sprintf(__('Submit as %s', 'publishpress-statuses'), $status->label));
                }
            }
        }

        if (empty($status->labels->save_as)) {
            if ('pending' == $status->name) {
                $status->labels->save_as = esc_html__('Save as Pending', 'publishpress-statuses');
            } elseif (!in_array($status->name, ['publish', 'private']) && empty($status->public) && empty($status->private)) {
                $status->labels->save_as = esc_attr(sprintf(__('Save as %s'), $status->label));
            } else {
                $status->labels->save_as = '';
            }
        }

        if (empty($status->labels->visibility)) {
            if ('publish' == $status->name) {
                $status->labels->visibility = esc_html__('Public');

            } elseif (!empty($status->public)) {
                $status->labels->visibility = (!defined('WPLANG') || ('en_EN' == WPLANG)) 
                ? esc_attr(sprintf(__('Public (%s)'), $status->label)) 
                : $status->label;  // not currently customizable by Edit Status UI
            
            } elseif (!empty($status->private)) {
                $status->labels->visibility = $status->label;
            }
        }

        return $status;
    }

    public static function get_selectable_statuses($post = false, $args = []) {
        if ($post && is_scalar($post)) {
            $post = get_post($post);
        }

        $is_administrator = \PublishPress_Statuses::isContentAdministrator();

        $post_status = (!empty($args['post_status'])) ? $args['post_status'] : $post->post_status;
        $post_type = (!empty($args['post_type'])) ? $args['post_type'] : $post->post_type;

        if (!empty($post)) {
            $post_status = apply_filters('presspermit_editor_ui_status', $post_status, $post, $args);
        }

        if ('auto-draft' == $post_status) {
            $post_status = 'draft';
        }

        if (!$post_status_obj = get_post_status_object($post_status)) {
            $post_status_obj = get_post_status_object('draft');
        }

        $moderation_statuses = \PublishPress_Statuses::getPostStati(['moderation' => true, 'internal' => false, 'post_type' => $post_type], 'object');
        unset($moderation_statuses['future']);

        $default_by_sequence = \PublishPress_Statuses::instance()->options->moderation_statuses_default_by_sequence;

        if ($post && $is_administrator && $default_by_sequence 
        && empty($post_status_obj->public) && empty($post_status_obj->private) && ('future' != $post_status) 
        && ! \PublishPress_Functions::isBlockEditorActive($post_type)) {
            $_publish_obj = get_post_status_object('publish');
            $_publish_obj->save_as = __('Publish', 'publishpress-statuses');
            $_publish_obj->publish = __('Advance Status', 'publishpress-statuses');
            $moderation_statuses['_public'] = $_publish_obj;
        }

        if (!$is_administrator) {
            $moderation_statuses = \PublishPress_Statuses::filterAvailablePostStatuses($moderation_statuses, $post_type, $post_status);
        }

        $moderation_statuses = apply_filters('presspermit_available_moderation_statuses', $moderation_statuses, $moderation_statuses, $post);

        $moderation_statuses = array_merge(['draft' => get_post_status_object('draft')], $moderation_statuses);

        // Don't exclude the current status, regardless of other arguments
        $_args = ['include_status' => $post_status_obj->name];

        if ($post) {
            if ($default_by_sequence) {
                if (!empty($post_status_obj->status_parent)) {
                    // If current status is a sub-status, only offer:
                    // * other sub-statuses in the same workflow branch
                    // * next status after current status
                    $_args['status_parent'] = $post_status_obj->status_parent;

                    // ['moderation_statuses' => [], 'can_set_status' => [], 'force_main_channel' => false, 'post_type' => '', 'default_by_sequence' => null, 'skip_current_status_check' => false];
                    if ($status_obj = \PublishPress_Statuses::getNextStatusObject($post->ID, compact('moderation_statuses', 'default_by_sequence', 'post_status'))) {
                        $_args['whitelist_status'] = $status_obj->name;
                    }
                } else {
                    // If current status is in main workflow, only display:
                    // * other top level workflow statuses
                    // * sub-statuses of the current status
                    $_args['status_parent'] = '';

                    if ($status_children = \PublishPress_Statuses::getStatusChildren($post_status_obj->name, $moderation_statuses)) {
                        // These statuses will not be added to the array if already removed by filterAvailablePostStatuses(),
                        // but will be exempted from the top level status_parent requirement
                        $_args['whitelist_status'] = array_keys($status_children);
                    }
                }
            }
        }

        $moderation_statuses = \PublishPress_Statuses::orderStatuses($moderation_statuses, $_args);

        return $moderation_statuses;
    }
}
