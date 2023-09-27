<?php
namespace PublishPress_Statuses;

// Custom Status management: Statuses Screen
class StatusesUI {
    private static $instance = null;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new \PublishPress_Statuses\StatusesUI(false);
            self::$instance->load();
        }

        return self::$instance;
    }

    public function __construct($do_load = true)
    {
        $this->version = PUBLISHPRESS_STATUSES_VERSION;

        if ($do_load) {
            $this->load();
        }
    }

    private function load() {
        $title = __('PublishPress Statuses', 'publishpress-statuses');

        add_filter('presspermit_edit_status_default_tab', [$this, 'fltEditStatusDefaultTab']);

        // Register our settings
        if (!empty($_REQUEST['page']) && ('publishpress-statuses-settings' === $_REQUEST['page'])) { 
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_init', [$this, 'handle_settings'], 100);
        }

        // Methods for handling the actions of creating, making default, and deleting post stati

        // @todo: REST
        if (!empty($_REQUEST['page']) && 0 === strpos($_REQUEST['page'], 'publishpress-statuses')) { 
            add_action('init', [$this, 'handle_add_custom_status'], 20);
            add_action('init', [$this, 'handle_edit_custom_status'], 20);
            add_action('init', [$this, 'handle_delete_custom_status'], 20);

            add_action('admin_init', [$this, 'handle_settings'], 100);
        }

        add_action('wp_ajax_pp_update_status_positions', [$this, 'handle_ajax_update_status_positions']);
        add_action('wp_ajax_pp_statuses_toggle_section', [$this, 'handle_ajax_pp_statuses_toggle_section']);
        add_action('wp_ajax_pp_delete_custom_status', [$this, 'handle_ajax_delete_custom_status']);

        add_action('init', function() {
            if (!empty($_REQUEST['page']) && ('publishpress-statuses' == $_REQUEST['page']) && !empty($_REQUEST['action']) && ('edit-status' == $_REQUEST['action'])) {
                $status_name = sanitize_key($_REQUEST['name']);
                if ($status_obj = get_post_status_object($status_name)) {
                    $title = sprintf(__('Edit Post Status: %s', 'publishpress-statuses'), $status_obj->label);
                }
            }
        }, 999);
    
        if (!empty($_REQUEST['page']) && ('publishpress-statuses-edit-status' == $_REQUEST['page'])) {
            $title = __('Edit Post Status', 'publishpress-statuses');

        } elseif (!empty($_REQUEST['page']) && ('publishpress-statuses-add-new' == $_REQUEST['page'])) {
            if (!empty($_REQUEST['taxonomy'])) {
                if ($tx = get_taxonomy(sanitize_key($_REQUEST['taxonomy']))) {
                    $title = sprintf(
                        __('Add %s Status', 'publishpress_statuses'),
                        $tx->label
                    );
                }
            } 
            
            if (empty($title)) {
                $title = __('Add Post Status', 'publishpress-statuses');
            }
        } elseif (!empty($_REQUEST['page']) && 'publishpress-hub' == $_REQUEST['page']) {
            $title = __('PublishPress Hub', 'publishpress-statuses');
        } else {
            $title = __('Post Statuses', 'publishpress-statuses');
        }

        $pp = \PublishPress_Statuses::instance();

        $pp->title = $title;

        /*
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
        */

        add_action('init', [$this, 'loadAdminMessages'], 999);

        // trigger 
        do_action('publishpress_plugin_screen', \PublishPress_Statuses::instance());
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

    public function loadAdminMessages() {
        /*
        if (!empty($_REQUEST['name'])) {
            if ($status_obj = get_post_status_object(sanitize_key($_REQUEST['name']))) {
                $status_name = ' ("' . $status_obj->label . '")';
                $url = \PublishPress_Statuses::getLink(['page' => 'publishpress-statuses', 'action' => 'edit-status', 'name' => sanitize_key($_REQUEST['name'])]);
                $edit_again = '&nbsp;&nbsp;<a href="' . esc_url($url) . '">' . esc_html__('Edit again', 'publishpress-statuses') . '</a>';
            }
        }
        */

        if (empty($status_name)) {
            $status_name = '';
            $edit_again = '';
        }

        \PublishPress_Statuses::instance()->messages = [
            'status-added' => __('Post status created. Select a tab for further configuration.', 'publishpress-statuses'),
            'status-updated' => sprintf(__('Post status%s updated. %s', 'publishpress-statuses'), $status_name, $edit_again),
            'status-missing' => __("Post status doesn't exist.", 'publishpress-statuses'),
            'default-status-changed' => __('Default post status has been changed.', 'publishpress-statuses'),
            'term-updated' => sprintf(__('Post status%s updated. %s', 'publishpress-statuses'), $status_name, $edit_again),
            'status-deleted' => __('Post status deleted.', 'publishpress-statuses'),
            'status-position-updated' => __("Status order updated.", 'publishpress-statuses'),
        ];
    }

    /**
     * Register settings for notifications so we can partially use the Settings API
     * (We use the Settings API for form generation, but not saving)
     *
     * @since 0.7
     */
    public function register_settings()
    {
        $group_name = \PublishPress_Statuses::SETTINGS_SLUG;

        if (!empty($group_name)) {
            add_settings_section(
                $group_name . '_general',
                false,
                '__return_false',
                $group_name
            );
            add_settings_field(
                'post_types',
                __('Use on these post types:', 'publishpress-statuses'),
                [$this, 'settings_post_types_option'],
                $group_name,
                $group_name . '_general'
            );

            add_settings_field(
                'moderation_statuses_default_by_sequence',
                __('Workflow sequence:', 'publishpress-statuses'),
                [$this, 'settings_moderation_statuses_default_by_sequence_option'],
                $group_name,
                $group_name . '_general'
            );

            if (function_exists('presspermit') && defined('PRESSPERMIT_COLLAB_VERSION') && defined('PRESSPERMIT_STATUSES_VERSION')) {
                add_settings_field(
                    'supplemental_cap_moderate_any',
                    __('Editor capabilities:', 'publishpress-statuses'),
                    [$this, 'settings_supplemental_cap_moderate_any_option'],
                    $group_name,
                    $group_name . '_general'
                );
            }
        }
    }

    public function settings_supplemental_cap_moderate_any_option() {
        $module = \PublishPress_Statuses::instance();
        
        echo '<div class="c-input-group">';

        echo sprintf(
            '<input type="hidden" name="%s" value="0" />',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[supplemental_cap_moderate_any]'
        ) . ' ';

        echo sprintf(
            '<input type="checkbox" name="%s" value="on" autocomplete="off" %s>',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[supplemental_cap_moderate_any]',
            $module->options->supplemental_cap_moderate_any ? 'checked' : ''
        ) . ' ';

        esc_html_e('Supplemental Role of Editor for "standard statuses" also covers Custom Statuses', 'publishpress-statuses');

        echo '</div>';
    }

    public function settings_moderation_statuses_default_by_sequence_option() {
        $module = \PublishPress_Statuses::instance();
        
        echo '<div class="c-input-group">';

        echo '<div>';

        echo sprintf(
            '<input type="hidden" name="%s" value="0" />',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[moderation_statuses_default_by_sequence]'
        ) . ' ';

        echo sprintf(
            '<input type="radio" name="%s" value="0" autocomplete="off" %s>',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[moderation_statuses_default_by_sequence]',
            !$module->options->moderation_statuses_default_by_sequence ? 'checked' : ''
        ) . ' ';

        esc_html_e('Publish button defaults to highest status available to user', 'publishpress-statuses');

        echo '</div><div style="margin-top: 10px;">';

        echo sprintf(
            '<input type="radio" name="%s" value="on" autocomplete="off" %s>',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[moderation_statuses_default_by_sequence]',
            $module->options->moderation_statuses_default_by_sequence ? 'checked' : ''
        ) . ' ';

        esc_html_e('Publish button defaults to next status in publication workflow', 'publishpress-statuses');

        echo '</div></div>';
    }

    public function settings_post_types_option($post_types = [])
    {
        $pp = \PublishPress_Statuses::instance();

        if (empty($pp->module)) {
            return;
        }

        if (empty($post_types)) {
            $post_types = [
                'post' => __('Posts'),
                'page' => __('Pages'),
            ];

            $custom_post_types = $pp->get_supported_post_types();

            foreach ($custom_post_types as $custom_post_type => $args) {
                $post_types[$custom_post_type] = $args->label;
            }
        }

        echo '<div class="pp-statuses-post-types">';

        foreach ($post_types as $post_type => $title) {
            echo '<label for="' . esc_attr($post_type) . '-' . $pp->module->slug . '">';
            echo '<input id="' . esc_attr($post_type) . '-' . $pp->module->slug . '" name="'
                . $pp->options_group_name . '[post_types][' . esc_attr($post_type) . ']"';
            
            if (isset($pp->options->post_types[$post_type])) {
                checked($pp->options->post_types[$post_type], true);
            }

            // Defining post_type_supports in the functions.php file or similar should disable the checkbox
            disabled(post_type_supports($post_type, 'pp_custom_statuses'), true);
            echo ' type="checkbox" value="on" />&nbsp;&nbsp;&nbsp;' . esc_html($title) . '</label>';
            
            // Leave a note to the admin as a reminder that add_post_type_support has been used somewhere in their code
            if (post_type_supports($post_type, 'pp_custom_statuses')) {
                echo '&nbsp&nbsp;&nbsp;<span class="description">' . sprintf(__('Disabled because add_post_type_support(\'%1$s\', \'%2$s\') is included in a loaded file.', 'publishpress-statuses'), $post_type, 'pp_custom_statuses') . '</span>';
            }
        }

        echo '</div>';
    }

    public function fltEditStatusDefaultTab($default_tab) {
        if (!$default_tab = \PublishPress_Functions::REQUEST_key('pp_tab')) {
            $default_tab = 'name';
        }

        return $default_tab;
    }

    /**
     * Primary configuration page for custom status class.
     * Shows form to add new custom statuses on the left and a
     * WP_List_Table with the custom status terms on the right
     */
    public function render_admin_page()
    {
        // @todo: separate "Add New" and Settings into separate modules

        $page = '';
        if (isset($_REQUEST['page'])) {
            $page = sanitize_text_field($_REQUEST['page']);
        }

        /** Full width view for editing a custom status **/
        if (isset($_GET['action'], $_GET['name']) && $_GET['action'] == 'edit-status'): 
            \PublishPress\ModuleAdminUI_Base::instance()->module->title = __('Edit Status', 'publishpress-statuses');
            \PublishPress\ModuleAdminUI_Base::instance()->default_header(''); //__('', 'publishpress-statuses'));

            require_once(__DIR__ . '/StatusEditUI.php');
            \PublishPress_Statuses\StatusEditUI::display();
        else: 

            if ($_GET['page'] === 'publishpress-statuses' && (empty($_GET['action']) || ('statuses' == $_GET['action']))) :
                \PublishPress\ModuleAdminUI_Base::instance()->default_header(__('Click any status property to edit. Drag to re-order, nest, or move to a different section.', 'publishpress-statuses'));
                
                // @todo: adapt old nav tab for status types (Pre-publication, Publication & Privacy, Revision Statuses)
                ?>
                <!--
                <div class='nav-tab-wrapper'>
                <a href="<?php
                    echo esc_url(\PublishPress_Statuses::getLink(['action' => 'statuses'])); ?>"
                        class="nav-tab<?php
                        if (empty($_GET['action']) || $_GET['action'] == 'statuses') {
                            echo ' nav-tab-active';
                        } ?>"><?php
                        _e('Statuses', 'publishpress-statuses'); ?></a>
    
                    <a href="<?php
                    echo esc_url(\PublishPress_Statuses::getLink(['action' => 'add-new'])); ?>"
                        class="nav-tab<?php
                        if (isset($_GET['action']) && $_GET['action'] == 'add-new') {
                            echo ' nav-tab-active';
                        } ?>"><?php
                        _e('Add New', 'publishpress-statuses'); ?></a>
                    
                    <a href="<?php
                    echo esc_url(\PublishPress_Statuses::getLink(['action' => 'options'])); ?>"
                        class="nav-tab<?php
                        if (isset($_GET['action']) && $_GET['action'] == 'options') {
                            echo ' nav-tab-active';
                        } ?>"><?php
                        _e('Settings', 'publishpress-statuses'); ?></a>
                </div>
                -->
                <?php
                
                require_once(__DIR__ . '/StatusListTable.php');
                $wp_list_table = new \PublishPress_Statuses\StatusListTable();
                $wp_list_table->prepare_items(); ?>

                <div id='co-l-right' class='pp-statuses-co-l-right'>
                    <div class='col-wrap' style="overflow: auto;">
                        <?php
                        $wp_list_table->display(); ?>
                        <?php
                        wp_nonce_field('custom-status-sortable', 'custom-status-sortable'); ?>
                    </div>
                </div>
            
            <?php else: 
                if ($_GET['page'] === 'publishpress-statuses-add-new') {
                    $title = (!empty($_REQUEST['taxonomy']) && 'post_visibility_pp' == $_REQUEST['taxonomy']) 
                    ?  __('Add New Visibility Status', 'publishpress-statuses')
                    :  __('Add New Pre-Publication Status', 'publishpress-statuses');

                    $descript = (!empty($_REQUEST['taxonomy']) && 'post_visibility_pp' == $_REQUEST['taxonomy']) 
                    ?  __('This status can be assigned to a post as a different form of Private Publication with its own capability requirements.', 'publishpress-statuses')
                    :  __('This status can be assigned to an unpublished post using the Post Status dropdown.', 'publishpress-statuses');

                    \PublishPress\ModuleAdminUI_Base::instance()->module->title = $title;
                    \PublishPress\ModuleAdminUI_Base::instance()->default_header($descript);

                } elseif ($_GET['page'] === 'publishpress-statuses-settings') {
                    \PublishPress\ModuleAdminUI_Base::instance()->module->title = __('PublishPress Statuses Settings', 'publishpress-statuses');
                    \PublishPress\ModuleAdminUI_Base::instance()->default_header(__('Note: Post types can also be specified for each individual status.', 'publishpress-statuses')); //$descript);
                }
            ?>
            <div id='co-l-left' class='pp-statuses-co-l-left'>
                <div class='col-wrap'>
                    <div class='form-wrap'>
                        <?php
                        if ($_GET['page'] === 'publishpress-statuses-add-new'):     
                        ?>
                            <?php
                            /** Custom form for adding a new Custom Status term **/ ?>
                            <form class='add:the-list:' action="<?php esc_url(\PublishPress_Statuses::getLink()); ?>"
                            method='post' id='addstatus' name='addstatus'>

                                <?php
                                wp_nonce_field('custom-status-add-nonce');

                                require_once(__DIR__ . '/StatusEditUI.php');
                                \PublishPress_Statuses\StatusEditUI::mainTabContent();
                                ?>
                                <input type="hidden" name="page" value="publishpress-statuses" />
                                <input type="hidden" name="action" value="add-status" />

                                <?php if (!empty($_REQUEST['taxonomy']) && (\PublishPress_Statuses::TAXONOMY_PRIVACY == $_REQUEST['taxonomy'])) :?>
                                <input type="hidden" name="taxonomy" value="<?php echo \PublishPress_Statuses::TAXONOMY_PRIVACY;?>" />
                                <?php endif;?>

                                <p class='submit'><?php
                                    submit_button(
                                        __('Add New Status', 'publishpress-statuses'),
                                        'primary',
                                        'submit',
                                        false
                                    ); ?>&nbsp;</p>
                            </form>
                        <?php
                        elseif ($_GET['page'] === 'publishpress-statuses-settings') : ?>
                            <form class='basic-settings'
                                    action="<?php
                                    echo esc_url(
                                        \PublishPress_Statuses::getLink(['action' => 'options'])
                                        // \PublishPress_Statuses::getLink(['action' => 'change-options'])
                                    ); ?>"
                                    method='post'>

                                <?php
                                settings_fields(\PublishPress_Statuses::SETTINGS_SLUG); ?>
                                <?php
                                do_settings_sections(\PublishPress_Statuses::SETTINGS_SLUG); ?>
                                <?php
                                echo '<input id="publishpress_module_name" name="publishpress_module_name[]" type="hidden" value="' . esc_attr(
                                        'publishpress_statuses'
                                    ) . '" />'; ?>

                                <br />

                                <?php
                                submit_button(); 
                                ?>

                                <?php
                                wp_nonce_field('edit-publishpress-settings'); ?>
                            </form>
                        <?php
                        endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php
        endif; ?>

        <?php
        if (did_action('publishpress_default_header')) {
            \PublishPress_Functions::publishpressFooter();
        }
        ?>
        </div>
        <?php
    }

    /**
     * Generate the color picker
     * $current_value   Selected icon for the status
     * fieldname        The name for the <select> field
     * $attributes      Insert attributes different to name and class. For example: 'id="something"'
     */
    public static function colorPicker($current_value = '', $fieldname = 'icon', $attributes = '')
    {
        // Load Color Picker
        if (is_admin()) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script(
                'publishpress-color-picker',
                PUBLISHPRESS_STATUSES_URL . 'common/libs/color-picker/color-picker.js',
                ['wp-color-picker'],
                false,
                true
            );
        }

        // Set default value if empty
        if (! empty($current_value)) {
            $pp_color = $current_value;
        } else {
            $pp_color = \PublishPress_Statuses::DEFAULT_COLOR;
        }

        $color_picker = '<input type="text" aria-required="true" size="7" maxlength="7" name="' . $fieldname . '" value="' . $pp_color . '" class="pp-color-picker" ' . $attributes . ' data-default-color="' . $pp_color . '" />';

        return $color_picker;
    }

    /**
     * Given a form field and a description, prints either the error associated with the field or the description.
     *
     * @param string $field The form field for which to check for an error
     * @param string $description Unlocalized string to display if there was no error with the given field
     *
     */
    public static function printErrorOrDescription($field, $description)
    {
        if (isset($_REQUEST['form-errors'][$field])): ?>
            <div class="form-error">
                <p><?php echo esc_html($_REQUEST['form-errors'][$field]); ?></p>
            </div>
        <?php else: ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif;
    }
}
