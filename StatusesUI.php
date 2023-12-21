<?php
namespace PublishPress_Statuses;

// Custom Status management: Statuses Screen
class StatusesUI {
    private static $instance = null;
    private $version;

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
        $plugin_page = \PublishPress_Functions::getPluginPage();

        $title = __('PublishPress Statuses', 'publishpress-statuses');

        add_filter('presspermit_edit_status_default_tab', [$this, 'fltEditStatusDefaultTab']);

        // Register our settings
        if ('publishpress-statuses-settings' === $plugin_page) { 
            add_action('admin_init', [$this, 'register_settings']);
        }

        if ('publishpress_custom_status_options' === \PublishPress_Functions::POST_key('option_page')) { 
            $this->handle_settings();
        }

        // Methods for handling the actions of creating, making default, and deleting post stati

        // @todo: REST
        if ((0 === strpos($plugin_page, 'publishpress-statuses'))
        && !\PublishPress_Functions::empty_POST('submit')
        ) { 
            $this->handle_add_custom_status();
            $this->handle_edit_custom_status();
            $this->handle_delete_custom_status();
        }

        if (('publishpress-statuses' === $plugin_page) 
        && ('edit-status' === \PublishPress_Functions::REQUEST_key('action'))) {
            $status_name = \PublishPress_Functions::REQUEST_key('name');

            if ($status_obj = get_post_status_object($status_name)) {
                // translators: %s is the status label
                $title = sprintf(__('Edit Post Status: %s', 'publishpress-statuses'), $status_obj->label);
            }

        } elseif ('publishpress-statuses-add-new' === $plugin_page) {
            if (!\PublishPress_Functions::empty_REQUEST('taxonomy')) {
                if ($tx = get_taxonomy(\PublishPress_Functions::REQUEST_key('taxonomy'))) {
                    $title = sprintf(
                        __('Add %s Status', 'publishpress_statuses'),
                        $tx->label
                    );
                }
            } 
            
            if (empty($title)) {
                $title = __('Add Post Status', 'publishpress-statuses');
            }
        } else {
            $title = __('Post Statuses', 'publishpress-statuses');
        }

        $pp = \PublishPress_Statuses::instance();

        $pp->title = $title;

        add_action('init', [$this, 'loadAdminMessages'], 999);

        do_action('publishpress_plugin_screen', \PublishPress_Statuses::instance());
    }

    /**
     * Handles a form's POST request to add a custom status
     */
    public function handle_add_custom_status()
    {
        if (('add-status' === \PublishPress_Functions::POST_key('action'))
        && \PublishPress_Functions::empty_REQUEST('settings_module')) {
            require_once(__DIR__ . '/StatusHandler.php');
            \PublishPress_Statuses\StatusHandler::handleAddCustomStatus();
        }
    }

    /**
     * Handles a POST request to edit a custom status
     */
    public function handle_edit_custom_status()
    {
        if (('edit-status' === \PublishPress_Functions::POST_key('action')) 
        && \PublishPress_Functions::empty_REQUEST('settings_module')) {
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
        if ('delete-status' === (\PublishPress_Functions::POST_key('action')) 
        && \PublishPress_Functions::empty_REQUEST('settings_module')) {
            require_once(__DIR__ . '/StatusHandler.php');
            \PublishPress_Statuses\StatusHandler::handleDeleteCustomStatus();
        }
    }

    /**
     * Handles a POST request to edit general status settings
     */
    public function handle_settings()
    {
        if (!\PublishPress_Functions::empty_POST('action')
        && ('publishpress_custom_status_options' === \PublishPress_Functions::POST_key('option_page'))
        ) {
            require_once(__DIR__ . '/StatusHandler.php');
            \PublishPress_Statuses\StatusHandler::settings_validate_and_save();
        }
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
        // Mechanism for "Edit again" link if we redirect back to Statuses screen after Edit Status update
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
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
            // translators: %1$s is the status name, %2$s is the edit link
            'status-updated' => sprintf(__('Post status %1$s updated. %2$s', 'publishpress-statuses'), $status_name, $edit_again),
            'settings-updated' => sprintf(__('Settings updated', 'publishpress-statuses')),
            'status-missing' => __("Post status doesn't exist.", 'publishpress-statuses'),
            'default-status-changed' => __('Default post status has been changed.', 'publishpress-statuses'),
            // translators: %1$s is the status name, %2$s is the edit link
            'term-updated' => sprintf(__('Post status%1$s updated. %2$s', 'publishpress-statuses'), $status_name, $edit_again),
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

            add_settings_field(
                'status_dropdown_show_current_branch_only',
                __('Status dropdown:', 'publishpress-statuses'),
                [$this, 'settings_status_dropdown_show_current_branch_only_option'],
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

        $checked = $module->options->supplemental_cap_moderate_any ? 'checked' : '';

        echo sprintf(
            '<input type="checkbox" name="%s" value="1" autocomplete="off" %s>',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[supplemental_cap_moderate_any]',
            esc_attr($checked)
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

        $checked = !$module->options->moderation_statuses_default_by_sequence ? 'checked' : '';

        echo sprintf(
            '<input type="radio" name="%s" value="0" autocomplete="off" %s>',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[moderation_statuses_default_by_sequence]',
            esc_attr($checked)
        ) . ' ';

        esc_html_e('Publish button defaults to highest status available to user', 'publishpress-statuses');

        echo '</div><div style="margin-top: 10px;">';

        $checked = $module->options->moderation_statuses_default_by_sequence ? 'checked' : '';

        echo sprintf(
            '<input type="radio" name="%s" value="1" autocomplete="off" %s>',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[moderation_statuses_default_by_sequence]',
            esc_attr($checked)
        ) . ' ';

        esc_html_e('Publish button defaults to next status in publication workflow', 'publishpress-statuses');

        echo '</div></div>';
    }

    public function settings_status_dropdown_show_current_branch_only_option() {
        $module = \PublishPress_Statuses::instance();
        
        echo '<div class="c-input-group">';

        echo sprintf(
            '<input type="hidden" name="%s" value="0" />',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[status_dropdown_show_current_branch_only]'
        ) . ' ';

        $checked = $module->options->status_dropdown_show_current_branch_only ? 'checked' : '';

        echo sprintf(
            '<input type="checkbox" name="%s" value="1" autocomplete="off" %s>',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[status_dropdown_show_current_branch_only]',
            esc_attr($checked)
        ) . ' ';

        esc_html_e('De-clutter the dropdown by hiding statuses outside current branch (if defaulting by sequence and some statuses are nested)', 'publishpress-statuses');

        echo '</div>';
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
            echo '<label for="' . esc_attr($post_type) . '-' . esc_attr($pp->module->slug) . '">';
            echo '<input id="' . esc_attr($post_type) . '-' . esc_attr($pp->module->slug) . '" name="'
                . esc_attr($pp->options_group_name) . '[post_types][' . esc_attr($post_type) . ']"';
            
            if (isset($pp->options->post_types[$post_type])) {
                checked($pp->options->post_types[$post_type], true);
            }

            // Defining post_type_supports in the functions.php file or similar should disable the checkbox
            disabled(post_type_supports($post_type, 'pp_custom_statuses'), true);
            echo ' type="checkbox" value="1" />&nbsp;&nbsp;&nbsp;' . esc_html($title) . '</label>';
            
            // Leave a note to the admin as a reminder that add_post_type_support has been used somewhere in their code
            if (post_type_supports($post_type, 'pp_custom_statuses')) {
                // translators: %1$s is the post type name, %2$s is the pp_custom_statuses feature
                echo '&nbsp&nbsp;&nbsp;<span class="description">' . sprintf(esc_html____('Disabled because add_post_type_support(\'%1$s\', \'%2$s\') is included in a loaded file.', 'publishpress-statuses'), esc_html($post_type), 'pp_custom_statuses') . '</span>';
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
     * Set up configuration page to administer statuses.
     */
    public function render_admin_page()
    {
        $plugin_page = \PublishPress_Functions::getPluginPage();

        $action = \PublishPress_Functions::GET_key('action');
        $enable_left_col = false;

        /** Edit Status screen **/
        if (('publishpress-statuses' === $plugin_page) && ('edit-status' == $action) && !\PublishPress_Functions::empty_REQUEST('name')) {
            $status_name = \PublishPress_Functions::REQUEST_key('name');
            $status_obj = get_post_status_object($status_name);
            $status_label = ($status_obj && !empty($status_obj->label)) ? $status_obj->label : $status_name;

            // translators: %s is the status name
            \PublishPress\ModuleAdminUI_Base::instance()->module->title = sprintf(__('Edit Status: %s', 'publishpress-statuses'), $status_label);
            \PublishPress\ModuleAdminUI_Base::instance()->default_header('');

            require_once(__DIR__ . '/StatusEditUI.php');
            \PublishPress_Statuses\StatusEditUI::display();

        /** Statuses screen **/
        } elseif (('publishpress-statuses' === $plugin_page) && (!$action || ('statuses' == $action))) {
            add_action('publishpress_header_button', function() {
                $status_type = \PublishPress_Functions::REQUEST_key('status_type');
                
                $args = [
                    'action' => 'add-new', 
                    'status_type' => $status_type
                ];
    
                if ('visibility' == $status_type) {
                    $args['taxonomy'] = 'post_visibility_pp';
                }
                
                $url = \PublishPress_Statuses::getLink(
                    $args
                );

                if (('visibility' != $status_type) || (defined('PRESSPERMIT_STATUSES_VERSION') && get_option('presspermit_privacy_statuses_enabled') )) {
                    echo '<a class="button primary add-new" title="' 
                        . esc_attr__("Add New Pre-Publication Status", 'publishpress-statuses')
                        . '" href="' . esc_url($url) . '">' . esc_html__('Add New') . '</a>';
                }
            });

            $status_type = \PublishPress_Functions::REQUEST_key('status_type');

            if (('visibility' == $status_type) && (!defined('PRESSPERMIT_PRO_VERSION') || !defined('PRESSPERMIT_STATUSES_VERSION') || !get_option('presspermit_privacy_statuses_enabled'))) {
                $headline = '';
            } else {
                $headline = esc_html__('Click any status property to edit. Drag to re-order, nest, or move to a different section.', 'publishpress-statuses');
            }

            \PublishPress\ModuleAdminUI_Base::instance()->default_header($headline);

            if ('visibility' == $status_type) {
                if (!defined('PRESSPERMIT_PRO_VERSION')) :?>
                    <div class="pp-statuses-config-notice">
                    <?php
                    printf(
                        esc_html__('Note: The %1$sPublishPress Permissions Pro%2$s plugin is required for custom Visibility Statuses, but %3$sis not active%4$s.', 'publishpress-statuses'),
                        '<a href="https://publishpress.com/permissions/" target="_blank">',
                        '</a>',
                        '<a href="' . esc_url(admin_url('plugins.php')) . '">',
                        '</a>'
                    );
                    ?>
                    </div>

                <?php elseif (!defined('PRESSPERMIT_STATUSES_VERSION')) :?>
                    <div class="pp-statuses-config-notice">
                    <?php
                    printf(
                        esc_html__('For custom Visibility Statuses, please %1$senable the Status Control module%2$s of Permissions Pro.', 'publishpress-statuses'),
                        '<a href="' . esc_url(admin_url('admin.php?page=presspermit-settings&pp_tab=modules')) . '">',
                        '</a>'
                    );
                    ?>
                    </div>

                <?php elseif (!get_option('presspermit_privacy_statuses_enabled')) :?>
                    <div class="pp-statuses-config-notice">
                    <?php
                    printf(
                        esc_html__('Note: Custom Visibility Statuses are %1$sdisabled%2$s.', 'publishpress-permissions'),
                        '<a href="' . esc_url(admin_url('admin.php?page=presspermit-settings&pp_tab=statuses')) . '">',
                        '</a>'
                    );
                    ?>
                    </div>

                <?php endif;
            }
            
            // @todo: adapt old nav tab for status types (Pre-publication, Publication & Privacy, Revision Statuses)
            ?>
            <div class='nav-tab-wrapper'>
            <a href="<?php
                if (!$status_type = \PublishPress_Functions::REQUEST_key('status_type')) {
                    $status_type = 'moderation';
                }

                echo esc_url(\PublishPress_Statuses::getLink(['action' => 'statuses', 'status_type' => 'moderation'])); ?>"
                    class="nav-tab<?php
                    if (!$action || ('moderation' == $status_type)) {
                        echo ' nav-tab-active';
                    } ?>"><?php
                    _e('Pre-Publication', 'publishpress-statuses'); ?></a>

                <a href="<?php
                echo esc_url(\PublishPress_Statuses::getLink(['action' => 'statuses', 'status_type' => 'visibility'])); ?>"
                    class="nav-tab<?php
                    if ('visibility' == $status_type) {
                        echo ' nav-tab-active';
                    } ?>"><?php
                    _e('Visibility', 'publishpress-statuses'); ?></a>
                
                <!--
                <a href="<?php
                echo esc_url(\PublishPress_Statuses::getLink(['action' => 'statuses', 'status_type' => 'revision'])); ?>"
                    class="nav-tab<?php
                    if ('revision' == $status_type) {
                        echo ' nav-tab-active';
                    } ?>"><?php
                    _e('Revision', 'publishpress-statuses'); ?></a>
                -->
            </div>
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
            
        <?php 
        /** Add New Status **/
        } elseif (isset($plugin_page) && ('publishpress-statuses-add-new' === $plugin_page)) {
            if (('post_visibility_pp'== \PublishPress_Functions::REQUEST_key('taxonomy')) && ! defined('PRESSPERMIT_STATUSES_VERSION')) {
                return;
            }

            $title = ('post_visibility_pp' == \PublishPress_Functions::REQUEST_key('taxonomy')) 
            ?  __('Add New Visibility Status', 'publishpress-statuses')
            :  __('Add New Pre-Publication Status', 'publishpress-statuses');

            $descript = ('post_visibility_pp' == \PublishPress_Functions::REQUEST_key('taxonomy'))
            ?  __('This status can be assigned to a post as a different form of Private Publication with its own capability requirements.', 'publishpress-statuses')
            :  __('This status can be assigned to an unpublished post using the Post Status dropdown.', 'publishpress-statuses');

            \PublishPress\ModuleAdminUI_Base::instance()->module->title = $title;
            \PublishPress\ModuleAdminUI_Base::instance()->default_header($descript);

            $enable_left_col = true;

        /** Status Settings **/
        } elseif (isset($plugin_page) && ('publishpress-statuses-settings' === $plugin_page)) {
            \PublishPress\ModuleAdminUI_Base::instance()->module->title = __('PublishPress Statuses Settings', 'publishpress-statuses');
            \PublishPress\ModuleAdminUI_Base::instance()->default_header(__('Note: Post types can also be specified for each individual status.', 'publishpress-statuses'));

            $enable_left_col = true;
        }

        if (!empty($enable_left_col)) :?>
            <div id='co-l-left' class='pp-statuses-co-l-left'>
                <div class='col-wrap'>
                    <div class='form-wrap'>
                        <?php
                        if ('publishpress-statuses-add-new' === $plugin_page) {
                            require_once(__DIR__ . '/StatusAddNewUI.php');

                        } elseif ('publishpress-statuses-settings' === $plugin_page) {
                            require_once(__DIR__ . '/StatusSettingsUI.php');
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endif;

        if (did_action('publishpress_default_header')) {
            \PublishPress\ModuleAdminUI_Base::defaultFooter(
                'publishpress-statuses',
                'PublishPress Statuses',
                'https://publishpress.com/statuses',
                'https://publishpress.com/documentation/statuses-start/',
                PUBLISHPRESS_STATUSES_URL . '/common/assets/publishpress-logo.png'
            );
        }
        ?>
        </div>
        <?php
    }

    /**
     * Generate the color picker
     * $current_value   Selected icon for the status
     * fieldname        The name for the <select> field
     * $attributes      Insert attributes different to name and class. For example: 'id' => "something"
     */
    public static function colorPicker($current_value = '', $fieldname = 'icon', $attributes = [])
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

        echo '<input type="text" aria-required="true" size="7" maxlength="7" name="' . esc_attr($fieldname) . '" value="' . esc_attr($pp_color) . '" class="pp-color-picker" data-default-color="' . esc_attr($pp_color) . '" />';
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
        if ($form_errors = \PublishPress_Statuses::instance()->form_errors): ?>
            <div class="form-error">
                <?php if (!empty($form_errors[$field])):?>
                <p><?php echo esc_html($form_errors[$field]); ?></p>
                <?php endif;?>
            </div>
        <?php else: ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif;
    }
}
