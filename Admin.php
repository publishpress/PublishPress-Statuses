<?php
namespace PublishPress_Statuses;

// Custom Status admin menus, shared javascript and CSS
class Admin
{
    function __construct() {
        /*
        add_action('publishpress_admin_menu_page', [$this, 'action_admin_menu_page']);
        add_action('publishpress_admin_submenu', [$this, 'action_admin_submenu']);
        */

        //add_action('presspermit_permissions_menu', [$this, 'act_permissions_menu'], 10, 2);
        add_action('admin_menu', [$this, 'act_admin_menu'], 21);

        // Load CSS and JS resources that we probably need
        add_action('admin_print_styles', [$this, 'add_admin_styles']);
        add_action('admin_enqueue_scripts', [$this, 'action_admin_enqueue_scripts']);
        add_action('admin_notices', [$this, 'no_js_notice']);
        add_action('admin_print_scripts', [$this, 'post_admin_header']);

        //add_action('wp_loaded', [$this, 'act_late_registrations']);
    }

    // late registration of statuses for PublishPress compat (PublishPress hooks to 'init' action at priority 1000)
    /*
    function act_late_registrations()
    {
        global $pagenow;

        if (in_array($pagenow, ['edit.php', 'post.php', 'post-new.php'])
            || (is_admin() && \PublishPress_Statuses::isAjax('inline-save'))
            || (in_array(presspermitPluginPage(), ['presspermit-status-edit', 'presspermit-status-new'], true))) {

            //self::set_status_labels();
        }
    }
    */

    function add_admin_styles() {
        global $pagenow;

        if ('admin.php' === $pagenow && isset($_GET['page']) && $_GET['page'] === 'publishpress-statuses') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            wp_enqueue_style('publishpress-settings-css', PUBLISHPRESS_STATUSES_URL . 'common/settings.css', false, PUBLISHPRESS_STATUSES_VERSION);

            wp_enqueue_style(
                'publishpress-statuses-css',
                PUBLISHPRESS_STATUSES_URL . 'common/custom-status.css',
                [],
                PUBLISHPRESS_STATUSES_VERSION
            );

            wp_enqueue_style('presspermit-admin-common', PUBLISHPRESS_STATUSES_URL . '/common/css/pressshack-admin.css', [], PUBLISHPRESS_STATUSES_VERSION);
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

        // Load Javascript we need to use on the configuration views (jQuery Sortable)
        if (!empty($pagenow) && ('admin.php' == $pagenow) 
        && (!empty($_REQUEST['page']) && ('publishpress-statuses' == $_REQUEST['page']))
        ) {
            //wp_enqueue_script('jquery-ui-sortable');

            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-datepicker');

            global $wp_post_statuses;

            wp_enqueue_script(
                'ui-touch-punch', 
                PUBLISHPRESS_STATUSES_URL . 'common/lib/jquery.ui.touch-punch.min.js', 
                ['jquery', 'jquery-ui-sortable'], 
                PUBLISHPRESS_STATUSES_VERSION
            );
            wp_enqueue_script(
                'nested-sortable-mjs-pp', 
                PUBLISHPRESS_STATUSES_URL . 'common/lib/jquery.mjs.nestedSortable-pp.js', 
                ['jquery', 'jquery-ui-sortable'], 
                PUBLISHPRESS_STATUSES_VERSION
            );

        //if (isset($_GET['page']) && $_GET['page'] === 'pp-modules-settings') {
            wp_enqueue_script(
                'publishpress-custom-status-configure',
                PUBLISHPRESS_STATUSES_URL . 'common/custom-status-configure.js',
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

            wp_enqueue_script(
                'publishpress-icon-preview',
                PUBLISHPRESS_STATUSES_URL . 'common/lib/icon-picker.js',
                ['jquery'],
                PUBLISHPRESS_STATUSES_VERSION,
                true
            );
            wp_enqueue_style(
                'publishpress-icon-preview',
                PUBLISHPRESS_STATUSES_URL . 'common/lib/icon-picker.css',
                ['dashicons'],
                PUBLISHPRESS_STATUSES_VERSION,
                'all'
            );

            wp_enqueue_style(
                'publishpress-custom_status-admin',
                PUBLISHPRESS_STATUSES_URL . 'common/custom-status-admin.css',
                false,
                PUBLISHPRESS_STATUSES_VERSION,
                'all'
            );
        }

        // Custom javascript to modify the post status dropdown where it shows up
        if ($this->is_whitelisted_page()) {
            /*
            wp_enqueue_script(
                'publishpress-custom_status',
                PUBLISHPRESS_STATUSES_URL . 'common/custom-status.js',
                ['jquery'],
                PUBLISHPRESS_STATUSES_VERSION,
                true
            );
            */

            if (class_exists('PublishPress_Functions')) { // @todo: refine library dependency handling
                if (\PublishPress_Functions::isBlockEditorActive()) {
                    wp_enqueue_style(
                        'publishpress-custom_status-block',
                        PUBLISHPRESS_STATUSES_URL . 'common/custom-status-block-editor.css',
                        false,
                        PUBLISHPRESS_STATUSES_VERSION,
                        'all'
                    );
                } else {
                    wp_enqueue_style(
                        'publishpress-custom_status',
                        PUBLISHPRESS_STATUSES_URL . 'common/custom-status.css',
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
        $ui = new \PublishPress_Statuses\StatusesUI();
        $ui->render_admin_page($this);
    }

    //function act_permissions_menu($options_menu, $handler)
    function act_admin_menu()
    {
        //if (defined('PRESSPERMIT_COLLAB_VERSION')) {
            //$this->menu_slug = 'publishpress-hub';
            $this->menu_slug = 'publishpress-statuses';

            $this->using_permissions_menu = true;

            /*
            add_menu_page(
                esc_html__('PublishPress Hub', 'publishpress-statuses'),
                esc_html__('PublishPress Hub', 'publishpress-statuses'),
                'read',
                'publishpress-hub',
                [$this, 'render_dashboard_page'],
                'dashicons-format-status',
                70
            );

            // If we are disabling native custom statuses in favor of PublishPress, 
            // but PP Collaborative Editing is not active, hide this menu item.
            add_submenu_page(
                'publishpress-hub',
                esc_html__('Post Statuses', 'publishpress-statuses'), 
                esc_html__('Post Statuses', 'publishpress-statuses'), 
                'manage_options',   // @todo: custom capability
                'publishpress-statuses', 
                [$this, 'render_admin_page']
            );
            */

            add_menu_page(
                esc_html__('Statuses', 'publishpress-statuses'),
                esc_html__('Statuses', 'publishpress-statuses'),
                'read',
                'publishpress-statuses',
                [$this, 'render_admin_page'],
                'dashicons-format-status',
                70
            );
        //}
    }

    function render_dashboard_page() {
        require_once(__DIR__ . '/StatusesUI.php');
        $ui = new \PublishPress_Statuses\StatusesUI();
        $ui->render_dashboard_page($this);
    }

    /**
     * Creates the admin menu if there is no menu set.
     */

    /*
    public function action_admin_menu_page()
    {
        global $publishpress;

        if (empty($publishpress) || defined('PRESSPERMIT_PRO_VERSION') || defined('PRESSPERMIT_VERSION')) {
            return;
        }

        if ($publishpress->get_menu_slug() !== self::MENU_SLUG) {
            return;
        }

        $publishpress->add_menu_page(
            esc_html__('Statuses', 'publishpress-statuses'),
            'read',
            self::MENU_SLUG,
            [$this, 'render_admin_page']
        );
    }
    */

    /**
     * Add necessary things to the admin menu
     */

    /*
    public function action_admin_submenu()
    {
        global $publishpress;
        
        //$submenu_slug = (defined('PRESSPERMIT_PRO_VERSION') || defined('PRESSPERMIT_VERSION')) 
        //? 'publishpress-statuses-shortcut'
        //: self::MENU_SLUG;

        if ((!function_exists('presspermit') || version_compare(PRESSPERMIT_VERSION, '3.9-beta', '<'))
        && !empty($publishpress)
        ) {
            $this->menu_slug = $publishpress->get_menu_slug();

            // Main Menu
            add_submenu_page(
                $this->menu_slug,
                esc_html__('Statuses', 'publishpress-statuses'),
                esc_html__('Statuses', 'publishpress-statuses'),
                'read',
                self::MENU_SLUG,
                [$this, 'render_admin_page'],
                5
            );
        }
    }
    */

    /**
     * Check whether custom status stuff should be loaded on this page
     *
     * @todo migrate this to the base module class
     */
    public function is_whitelisted_page()
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
        if (isset($_GET['vcv-action']) && $_GET['vcv-action'] === 'frontend') {
            return false;
        }

        // Only add the script to Edit Post and Edit Page pages -- don't want to bog down the rest of the admin with unnecessary javascript
        return in_array(
            $pagenow,
            ['post.php', 'edit.php', 'post-new.php', 'page.php', 'edit-pages.php', 'page-new.php']
        );
    }

    /**
     * Displays a notice to users if they have JS disabled
     * Javascript is needed for custom statuses to be fully functional
     */
    public function no_js_notice()
    {
        if ($this->is_whitelisted_page()) :
            ?>
            <style type="text/css">
                /* Hide post status dropdown by default in case of JS issues **/

                /*
                label[for=post_status],
                #post-status-display,
                #post-status-select,
                #publish {
                    display: none;
                }
                */
            </style>
            <div class="update-nag hide-if-js">
                <?php
                _e(
                    '<strong>Note:</strong> Your browser does not support JavaScript or has JavaScript disabled. You will not be able to access or change the post status.',
                    'publishpress-statuses'
                ); ?>
            </div>
        <?php
        endif;
    }

    /**
     * Adds all necessary javascripts to make custom statuses work
     *
     * @todo Support private and future posts on edit.php view
     */
    public function post_admin_header()
    {
        global $post, $pagenow, $current_user;

        if (\PublishPress_Statuses::DisabledForPostType()) {
            return;
        }

        // Get current user
        wp_get_current_user();

        if ($this->is_whitelisted_page()) {
            $post_type_obj = get_post_type_object(\PublishPress_Statuses::getCurrentPostType());
            $custom_statuses = \PublishPress_Statuses::getPostStati([], 'object');  // @todo: confirm inclusion of core statuses here
            $selected = null;
            $selected_name = __('Draft', 'publishpress-statuses');

            $custom_statuses = apply_filters('pp_custom_status_list', $custom_statuses, $post);

            // Only add the script to Edit Post and Edit Page pages -- don't want to bog down the rest of the admin with unnecessary javascript
            if (! empty($post)) {
                //get raw post so custom post status is included
                $post = get_post($post);
                // Get the status of the current post
                if ($post->ID == 0 || $post->post_status == 'auto-draft' || $pagenow == 'edit.php') {
                    // TODO: check to make sure that the default exists
                    $selected = \PublishPress_Statuses::DEFAULT_STATUS;
                } else {
                    $selected = $post->post_status;
                }

                if (empty($selected)) {
                    $selected = \PublishPress_Statuses::DEFAULT_STATUS;
                }

                // Get the current post status name

                foreach ($custom_statuses as $status) {
                    if ($status->name == $selected) {
                        $selected_name = $status->label;
                    }
                }
            }

            $all_statuses = [];

            // Load the custom statuses
            foreach ($custom_statuses as $status) {
                // @todo: function argument?
                if (!empty($status->private) && ('private' != $status->name)) {
                    continue;
                }

                $all_statuses[] = [
                    'label' => esc_js(\PublishPress_Statuses::get_status_property($status, 'label')),
                    'name' => esc_js(\PublishPress_Statuses::get_status_property($status, 'name')),
                    'description' => esc_js(\PublishPress_Statuses::get_status_property($status, 'description')),
                    'color' => esc_js(\PublishPress_Statuses::get_status_property($status, 'color')),
                    'icon' => esc_js(\PublishPress_Statuses::get_status_property($status, 'icon')),

                ];
            }

            // TODO: Move this to a script localization method. 
            ?>
            <script type="text/javascript">
                var pp_text_no_change = '<?php echo esc_js(__("&mdash; No Change &mdash;")); ?>';
                var label_save = '<?php echo __('Save'); ?>';
                var pp_default_custom_status = '<?php echo esc_js(\PublishPress_Statuses::DEFAULT_STATUS); ?>';
                var current_status = '<?php echo esc_js($selected); ?>';
                var current_status_name = '<?php echo esc_js($selected_name); ?>';
                var custom_statuses = <?php echo json_encode($all_statuses); ?>;
                var current_user_can_publish_posts = <?php echo current_user_can(
                    $post_type_obj->cap->publish_posts
                ) ? 1 : 0; ?>;
                var current_user_can_edit_published_posts = <?php echo current_user_can(
                    $post_type_obj->cap->edit_published_posts
                ) ? 1 : 0; ?>;
            </script>
            <?php
        }
    }


    // @todo: merge into getPostStatuses() / register_post_status() calls

    public static function set_status_labels($status)
    {
        /*
            if (!empty($status_args['moderation'])) {
                if (defined('PP_NO_MODERATION'))
                    return $status;
            }
        */

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
                    $status->labels->publish = esc_attr(sprintf(__('Set to %s', 'publishpress-statuses'), $status->label));
                } else {
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

        if (!$is_administrator) {
            $moderation_statuses = \PublishPress_Statuses::filterAvailablePostStatuses($moderation_statuses, $post_type, $post_status);
        }

        $moderation_statuses = apply_filters('presspermit_available_moderation_statuses', $moderation_statuses, $moderation_statuses, $post);

        $moderation_statuses = array_merge(['draft' => get_post_status_object('draft')], $moderation_statuses);

        // Don't exclude the current status, regardless of other arguments
        $_args = ['include_status' => $post_status_obj->name];

        if ($post) {
            if ($default_by_sequence = \PublishPress_Statuses::instance()->options->moderation_statuses_default_by_sequence) {
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

        /*
        if (!empty($post_status_obj->status_parent)) {
            if ($default_by_sequence) {
                // If current status is a workflow branch child, only offer other statuses in that branch
                $_args['status_parent'] = $post_status_obj->status_parent;
            }
        } elseif ($status_children = \PublishPress_Statuses::getStatusChildren($post_status_obj->name, $moderation_statuses)) {
            if ($default_by_sequence) {
                // If current status is a workflow branch parent, only offer other statuses in that branch
                $moderation_statuses = array_merge([$post_status_obj->name => $post_status_obj], $status_children);
            }
        } else {
            // If current status is in main workflow with no branch children, only display other main workflow statuses 
            $_args['status_parent'] = '';
        }
        */

        $moderation_statuses = \PublishPress_Statuses::orderStatuses($moderation_statuses, $_args);

        return $moderation_statuses;
    }
}
