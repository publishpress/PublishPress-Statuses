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

        add_filter('presspermit_edit_status_default_tab', [$this, 'fltEditStatusDefaultTab']);

        // Register our settings
        if ('publishpress-statuses-settings' === $plugin_page) { 
            add_action('admin_init', [$this, 'register_settings']);
        }

        if ('publishpress_custom_status_options' === \PublishPress_Functions::POST_key('option_page')) { 
            add_action('admin_init', function() {
                $this->handle_settings();
            });
        }

        // Methods for handling the actions of creating, making default, and deleting post stati

        // @todo: REST
        if ((0 === strpos($plugin_page, 'publishpress-statuses'))
        && !\PublishPress_Functions::empty_POST('submit')
        ) {
            add_action('init', function() {
                $this->handle_add_custom_status();
                $this->handle_edit_custom_status();
                $this->handle_delete_custom_status();
            }, 20);
        }

        $title = '';

        if (('publishpress-statuses' === $plugin_page) 
        && ('edit-status' === \PublishPress_Functions::REQUEST_key('action'))) {
            $status_name = \PublishPress_Functions::REQUEST_key('name');

            if ($status_obj = get_post_status_object($status_name)) {
                // translators: %s is the status label
                $title = sprintf(__('Edit Post Status: %s', 'publishpress-statuses'), $status_obj->label);

                // translators: %s is the status label
                $custom_html_title = sprintf(__('%s Status - Edit', 'publishpress-statuses'), $status_obj->label);
            }

        } elseif ('publishpress-statuses-add-new' === $plugin_page) {
            if (!\PublishPress_Functions::empty_REQUEST('taxonomy')) {
                if ($tx = get_taxonomy(\PublishPress_Functions::REQUEST_key('taxonomy'))) {
                    $title = sprintf(
                        // translators: %s is status type: "Workflow", "Visibility", "Revision", etc.
                        __('Add %s Status', 'publishpress-statuses'),
                        $tx->label
                    );
                }
            } 

            if (empty($title)) {
                $title = __('Add Post Status', 'publishpress-statuses');
                $custom_html_title = sprintf(__('Add Status', 'publishpress-statuses'));
            }

        } elseif ('publishpress-statuses-settings' === $plugin_page) {
            $title = __('PublishPress Statuses Settings', 'publishpress-statuses');
            $custom_html_title = sprintf(__('Statuses Settings', 'publishpress-statuses'));

        } else {
            $title = __('Post Statuses', 'publishpress-statuses');
            $custom_html_title = __('Statuses', 'publishpress-statuses');
            
            if ($status_type = \PublishPress_Functions::REQUEST_key('status_type')) { // @todo: implement hook in Permissions
                $_title = $title;
                
                if ('visibility' == $status_type) {
                    $title = __('Visibility Statuses', 'publishpress-statuses');
                } else {
                    $title = apply_filters('publishpress_statuses_admin_title', $title, $status_type);
                }

                if ($title != $_title) {
                    $custom_html_title = $title;
                }
            }
        }

        $pp_statuses = \PublishPress_Statuses::instance();

        $pp_statuses->title = $title;

        if (empty($custom_html_title)) {
            $custom_html_title = $title;
        }

        add_filter('admin_title', function($admin_title, $_title) use ($custom_html_title) {
            return $custom_html_title;
        }, 10, 2);

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
            'status-missing' => __("That post status doesn't seem to exist. Was a required plugin or setting deactivated?", 'publishpress-statuses'),
            'default-status-changed' => __('Default post status has been changed.', 'publishpress-statuses'),
            // translators: %1$s is the status name, %2$s is the edit link
            'term-updated' => sprintf(__('Post status %1$s updated. %2$s', 'publishpress-statuses'), $status_name, $edit_again),
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
                'moderation_statuses_default_by_sequence',
                __('Workflow Guidance:', 'publishpress-statuses'),
                [$this, 'settings_moderation_statuses_default_by_sequence_option'],
                $group_name,
                $group_name . '_general',
                ['class' => 'pp-settings-space-bottom']
            );

            add_settings_field(
                'status_dropdown_show_current_branch_only',
                __('Sub-Status Selection:', 'publishpress-statuses'),
                [$this, 'settings_status_dropdown_show_current_branch_only_option'],
                $group_name,
                $group_name . '_general',
                ['class' => 'pp-statuses-settings-status-declutter pp-settings-space-bottom']
            );

            add_settings_field(
                'post_types',
                __('Use on these Post Types:', 'publishpress-statuses'),
                [$this, 'settings_post_types_option'],
                $group_name,
                $group_name . '_general',
                ['class' => 'pp-settings-space-top pp-settings-separation-bottom']
            );

            $show_edit_caps_setting = (function_exists('presspermit') && defined('PRESSPERMIT_COLLAB_VERSION') && defined('PRESSPERMIT_STATUSES_VERSION'));

            add_settings_field(
                'status_dropdown_pending_status_regulation',
                __('Pending Review Status:', 'publishpress-statuses'),
                [$this, 'settings_pending_status_regulation_option'],
                $group_name,
                $group_name . '_general',
                ($show_edit_caps_setting) ? ['class' => 'pp-settings-separation-top'] : ['class' => 'pp-settings-separation-top pp-settings-separation-bottom']
            );

            if ($show_edit_caps_setting) {
                add_settings_field(
                    'supplemental_cap_moderate_any',
                    __('Permissions Integration:', 'publishpress-statuses'),
                    [$this, 'settings_supplemental_cap_moderate_any_option'],
                    $group_name,
                    $group_name . '_general',
                    ['class' => 'pp-settings-separation-bottom']
                );
            }

            add_settings_field(
                'force_editor_detection',
                __('Gutenberg / Classic Editor:', 'publishpress-statuses'),
                [$this, 'settings_force_editor_detection_option'],
                $group_name,
                $group_name . '_general',
                ['class' => 'pp-settings-separation-top']
            );

            add_settings_field(
                'label_storage',
                __('Status Label Customization:', 'publishpress-statuses'),
                [$this, 'settings_label_storage_option'],
                $group_name,
                $group_name . '_general',
                ['class' => 'pp-settings-separation-bottom']
            );

            if (!defined('PUBLISHPRESS_STATUSES_NO_PLANNER_IMPORT')) {
                $terms = get_terms('post_status', ['hide_empty' => false]);

                if ($show_import_setting = !empty($terms) 
                && (get_option('publishpress_version') || get_site_option('edit_flow_version', false) || get_option('pps_version') || defined('PP_STATUSES_ENABLE_PLANNER_IMPORT'))
                ) {
                    add_settings_field(
                        'import_operation',
                        __('Import Operation:', 'publishpress-statuses'),
                        [$this, 'settings_import_operation_option'],
                        $group_name,
                        $group_name . '_general',
                        ['class' => 'pp-settings-separation-top']
                    );
                }
            }

            add_settings_field(
                'backup_operation',
                __('Backup / Restore:', 'publishpress-statuses'),
                [$this, 'settings_backup_operation_option'],
                $group_name,
                $group_name . '_general',
                (!empty($show_import_setting)) ? ['class' => 'pp-settings-separation-bottom'] : ['class' => 'pp-settings-separation-top pp-settings-separation-bottom']
            );

            do_action('publishpress_statuses_add_settings_field', $group_name);
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
            '<input type="checkbox" name="%s" id="supplemental_cap_moderate_any" value="1" autocomplete="off" %s>',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[supplemental_cap_moderate_any]',
            esc_attr($checked)
        ) . ' ';

        echo '<label for="supplemental_cap_moderate_any">';
        esc_html_e('Supplemental Role of Editor covers custom statuses', 'publishpress-statuses');
        echo '</label>';

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

        $checked = $module->options->moderation_statuses_default_by_sequence ? 'checked' : '';

        echo sprintf(
            '<input type="radio" name="%s" id="moderation_statuses_default_to_next" value="1" autocomplete="off" %s>',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[moderation_statuses_default_by_sequence]',
            esc_attr($checked)
        ) . ' ';

        echo '<label for="moderation_statuses_default_to_next">';
        esc_html_e('Sequence by default: Publish button defaults to next status in workflow', 'publishpress-statuses');
        echo '</label>';

        echo '</div><div style="margin-top: 12px;">';

        $checked = !$module->options->moderation_statuses_default_by_sequence ? 'checked' : '';

        echo sprintf(
            '<input type="radio" name="%s" id="moderation_statuses_default_to_highest" value="0" autocomplete="off" %s>',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[moderation_statuses_default_by_sequence]',
            esc_attr($checked)
        ) . ' ';

        echo '<label for="moderation_statuses_default_to_highest">';
        esc_html_e('Bypass by default: Publish button defaults to maximum available status', 'publishpress-statuses');
        echo '</label>';

        echo '</div></div>';

        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function ($) {
            <?php if ($module->options->moderation_statuses_default_by_sequence):?>
            $('tr.pp-statuses-settings-status-declutter').show();
            <?php endif;?>

            $('#moderation_statuses_default_to_next').on('change', function() {
                $('tr.pp-statuses-settings-status-declutter').toggle($(this).val());
            });

            $('#moderation_statuses_default_to_highest').on('change', function() {
                $('tr.pp-statuses-settings-status-declutter').toggle($(this).val());
            });
        });
        /* ]]> */
        </script>
        <?php
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
            '<input type="checkbox" name="%s" id="status_dropdown_show_current_branch_only" value="1" autocomplete="off" %s>',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[status_dropdown_show_current_branch_only]',
            esc_attr($checked)
        ) . ' ';

        echo '<label for="status_dropdown_show_current_branch_only">';
        esc_html_e('Hide nested statuses (workflow branches) in the dropdown unless the post is set to the parent status or a sibling', 'publishpress-statuses');
        echo '</label>';

        echo '</div>';
    }

    public function settings_pending_status_regulation_option() {
        $module = \PublishPress_Statuses::instance();
        
        echo '<div class="c-input-group">';

        $option_val = !empty($module->options->pending_status_regulation) ? $module->options->pending_status_regulation : '';

        echo sprintf(
            '<select id="pending_status_regulation" name="%s" autocomplete="off">',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[pending_status_regulation]'
        );

        ?>
        <option value='' <?php if (empty($option_val)) echo "selected";?>><?php esc_html_e('Available to all users', 'publishpress-statuses');?></option>
        <option value='1' <?php if ($option_val) echo "selected";?>><?php esc_html_e('Available to specified roles only', 'publishpress-statuses');?></option>
        </select> 

        <p class="pp-option-footnote">
        <?php
        if ($option_val) {
            esc_html_e('Users can only assign a custom status to a post if their role allows it. With the current setting, the same control is applied to the Pending Review status.', 'publishpress-statuses');
        } else {
            esc_html_e('Users can only assign a custom status to a post if their role allows it. With the current setting, those limitations will not be applied for the Pending Review status.', 'publishpress-statuses');
        }
        ?>
        </p>

        <p class="pp-option-footnote pp-pending-specified" <?php if (!$option_val) echo 'style="display:none;"';?>>
        <?php
        printf(
            // translators: %1$s and %2$s are link markup
            esc_html__('View or set roles at %1$s Statuses > Statuses > Pending Review > Roles %2$s', 'publishpress-statuses'),
            '<a href="' . esc_url(admin_url('admin.php?action=edit-status&name=pending&page=publishpress-statuses&pp_tab=roles')) . '">',
            '</a>'
        );
        ?>
        </p>

        <?php
        echo '</div>';
    }

    public function settings_force_editor_detection_option() {
        $module = \PublishPress_Statuses::instance();
        
        echo '<div class="c-input-group">';

        $option_val = !empty($module->options->force_editor_detection) ? $module->options->force_editor_detection : '';

        echo sprintf(
            '<select name="%s" autocomplete="off">',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[force_editor_detection]'
        );

        ?>
        <option value='' <?php if (empty($option_val)) echo "selected";?>><?php esc_html_e('Automatic Detection', 'publishpress-statuses');?></option>
        <option value='classic' <?php if ('classic' === $option_val) echo "selected";?>><?php esc_html_e('Using Classic Editor', 'publishpress-statuses');?></option>
        <option value='gutenberg' <?php if ('gutenberg' === $option_val) echo "selected";?>><?php esc_html_e('Using Gutenberg Editor', 'publishpress-statuses');?></option>
        </select> 

        <p class="pp-option-footnote">
        <?php
        esc_html_e('If custom statuses in the post editor are not loaded correctly, prevent incorrect detection of editor by specifying it here.', 'publishpress-statuses');
        ?>
        </p>

        <?php
        echo '</div>';
    }

    public function settings_label_storage_option() {
        $module = \PublishPress_Statuses::instance();
        
        echo '<div class="c-input-group">';

        $option_val = !empty($module->options->label_storage) ? $module->options->label_storage : '';

        echo sprintf(
            '<select name="%s" autocomplete="off">',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[label_storage]'
        );

        ?>
        <option value='' <?php if (empty($option_val)) echo "selected";?>><?php esc_html_e('For all plugin statuses', 'publishpress-statuses');?></option>
        <option value='user' <?php if ('user' === $option_val) echo "selected";?>><?php esc_html_e('For user-created plugin statuses only', 'publishpress-statuses');?></option>
        </select> 

        <p class="pp-option-footnote">
        <?php
        esc_html_e('This controls which statuses can have their labels customized by editing Status properties. If a non-default entry is stored, it will override any language file strings.', 'publishpress-statuses');
        ?>
        </p>

        <?php
        echo '</div>';
    }

    public function settings_post_types_option($unused = [])
    {
        $pp = \PublishPress_Statuses::instance();

        if (empty($pp->module)) {
            return;
        }

        if (empty($post_types)) {
            $post_types = [
                'post' => \PublishPress_Statuses::__wp('Posts'),
                'page' => \PublishPress_Statuses::__wp('Pages'),
            ];

            $custom_post_types = $pp->get_supported_post_types();

            foreach ($custom_post_types as $custom_post_type => $args) {
                $post_types[$custom_post_type] = $args->label;
            }
        }
        ?>

        <div class="pp-statuses-post-types">

        <?php
        foreach ($post_types as $post_type => $title) {
            echo sprintf(
                '<input type="hidden" name="%s" value="0" />',
                esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[post_types][' . esc_attr($post_type) . ']"'
            ) . ' ';

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
        ?>

        </div>

        <p class="pp-option-footnote">
        <?php
        _e('Note: Post types can also be specified for each individual status.', 'publishpress-statuses');
        ?>
        </p>
        <?php
    }

    public function settings_import_operation_option() {
        $module = \PublishPress_Statuses::instance();
        ?>

        <div class="c-input-group">

        <select name="publishpress_statuses_import_operation" autocomplete="off">

        <option value=''><?php esc_html_e('Select...', 'publishpress-statuses');?></option>

        <?php if (get_option('pps_version')):?>
        <option value='do_status_control_import'><?php esc_html_e('Re-run Planner import (with Status Control properties, ordering)', 'publishpress-statuses');?></option>
        <option value='do_planner_import_only'><?php esc_html_e('Re-run Planner import (ignoring Status Control configuration)', 'publishpress-statuses');?></option>
        <?php else:?>
        <option value='do_planner_import'><?php esc_html_e('Re-run Planner import', 'publishpress-statuses');?></option>
        <?php endif;?>
        </select> 

        <p class="pp-option-footnote">
        <?php
        esc_html_e('Status color, icon and position is automatically imported from PublishPress Planner. Select an option above to re-run the import, giving priority to Planner-defined status position.', 'publishpress-statuses');
        ?>
        </p>

        </div>

        <?php
    }

    public function settings_backup_operation_option() {
        $module = \PublishPress_Statuses::instance();
        
        $meta_missing = array_fill_keys(['color', 'icon', 'labels', 'post_type', 'backup_color', 'backup_icon', 'backup_labels', 'backup_post_type', 'backup_color_', 'backup_icon_', 'backup_labels_', 'backup_post_type_'], true);

        $all_statuses = \PublishPress_Statuses::getPostStati(['internal' => false], 'object');

        $_terms = get_terms(\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, ['hide_empty' => false]);

        if ($_terms) {
            foreach($_terms as $term) {
                $term_meta = get_term_meta($term->term_id);

                foreach (array_keys($meta_missing) as $prop) {
                    if (!empty($term_meta[$prop])) {
                        if (in_array($prop, ['color', 'icon', 'labels', 'post_types'])) {
                            // "Use defaults" operation only applies to our built-in statuses
                            if (empty($all_statuses[$term->slug]) || empty($all_statuses[$term->slug]->pp_builtin)) {
                                continue;
                            }
                        }

                        unset($meta_missing[$prop]);
                    }
                }

                if (!$meta_missing) {
                    break;
                }
            }
        }
        ?>

        <div class="c-input-group">

        <select name="publishpress_statuses_backup_operation" autocomplete="off">

        <option value=''><?php esc_html_e('Select...', 'publishpress-statuses');?></option>

        <option value='backup_status_properties'><?php esc_html_e('Back up current colors, icons, labels and post types', 'publishpress-statuses');?></option>

        <option value=''></option>

        <?php if (empty($backup_missing['backup_color'])):?>
        <option value='restore_status_colors'><?php esc_html_e('Restore backup of status colors', 'publishpress-statuses');?></option>
        <?php endif;?>

        <?php if (empty($backup_missing['backup_icon'])):?>
        <option value='restore_status_icons'><?php esc_html_e('Restore backup of status icons', 'publishpress-statuses');?></option>
        <?php endif;?>

        <?php if (empty($backup_missing['backup_labels'])):?>
        <option value='restore_status_labels'><?php esc_html_e('Restore backup of status labels', 'publishpress-statuses');?></option>
        <?php endif;?>

        <?php if (empty($backup_missing['backup_post_type'])):?>
        <option value='restore_status_post_types'><?php esc_html_e('Restore backup of status post types', 'publishpress-statuses');?></option>
        <?php endif;?>

        <option value=''></option>

        <?php if (empty($backup_missing['backup_color_'])):?>
        <option value='restore_status_colors_auto'><?php esc_html_e('Restore auto-backup of status colors', 'publishpress-statuses');?></option>
        <?php endif;?>

        <?php if (empty($backup_missing['backup_icon_'])):?>
        <option value='restore_status_icons_auto'><?php esc_html_e('Restore auto-backup of status icons', 'publishpress-statuses');?></option>
        <?php endif;?>

        <?php if (empty($backup_missing['backup_labels_'])):?>
        <option value='restore_status_labels_auto'><?php esc_html_e('Restore auto-backup of status labels', 'publishpress-statuses');?></option>
        <?php endif;?>

        <?php if (empty($backup_missing['backup_post_type_'])):?>
        <option value='restore_status_post_types_auto'><?php esc_html_e('Restore auto-backup of status post types', 'publishpress-statuses');?></option>
        <?php endif;?>


        <option value=''></option>

        <?php if (empty($backup_missing['color'])):?>
        <option value='default_status_colors'><?php esc_html_e('Use default status colors', 'publishpress-statuses');?></option>
        <?php endif;?>

        <?php if (empty($backup_missing['icon'])):?>
        <option value='default_status_icons'><?php esc_html_e('Use default status icons', 'publishpress-statuses');?></option>
        <?php endif;?>

        <?php if (empty($backup_missing['labels'])):?>
        <option value='default_status_labels'><?php esc_html_e('Use default status labels', 'publishpress-statuses');?></option>
        <?php endif;?>

        <?php if (empty($backup_missing['post_type'])):?>
        <option value='default_status_post_types'><?php esc_html_e('Use default status post types', 'publishpress-statuses');?></option>
        <?php endif;?>

        <?php if (get_option('publishpress_version') || defined('PP_STATUSES_OFFER_PLANNER_DEFAULTS')):?>
        <option value='default_status_colors_planner'><?php esc_html_e("Use Planner plugin's default status colors", 'publishpress-statuses');?></option>
        <?php endif;?>

        <?php if (get_option('publishpress_version') || defined('PP_STATUSES_OFFER_PLANNER_DEFAULTS')):?>
        <option value='default_status_icons_planner'><?php esc_html_e("Use Planner plugin's default status icons", 'publishpress-statuses');?></option>
        <?php endif;?>

        </select> 

        </div>
        <?php
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
            
            $status_obj = \PublishPress_Statuses::getStatusBy('id', $status_name);

            $status_label = ($status_obj && !empty($status_obj->label)) ? $status_obj->label : $status_name;

            $tx_obj = get_taxonomy($status_obj->taxonomy);

            if (('post_status' == $status_obj->taxonomy) || !$tx_obj || empty($tx_obj->labels) || empty($tx_obj->labels->singular_name)) {
                // translators: %s is the status name
                $title = sprintf(__('Edit Status: %s', 'publishpress-statuses'), $status_label);
            } else {
                // translators: %s is the status name
                $title = sprintf(__('Edit %1$s: %2$s', 'publishpress-statuses'), $tx_obj->labels->singular_name, $status_label);
            }

            \PublishPress\ModuleAdminUI_Base::instance()->module->title = $title;
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
    
                switch ($status_type) {
                    case 'visibility' :
                        $args['taxonomy'] = 'post_visibility_pp';
                        break;

					default:
						if ($_taxonomy = apply_filters('publishpress_statuses_status_type_to_taxonomy', '', $status_type)) {
							$args['taxonomy'] = $_taxonomy;	
						}
                }

                $url = \PublishPress_Statuses::getLink(
                    $args
                );

                if (('visibility' != $status_type) || (defined('PRESSPERMIT_STATUSES_VERSION') && get_option('presspermit_privacy_statuses_enabled') )) {
                    echo '<a class="button primary add-new" title="' 
                        . esc_attr__("Add New Pre-Publication Status", 'publishpress-statuses')
                        . '" href="' . esc_url($url) . '">' . esc_html__('Add New', 'publishpress-statuses') . '</a>';
                }
            });

            $status_type = \PublishPress_Functions::REQUEST_key('status_type');

            \PublishPress\ModuleAdminUI_Base::instance()->default_header();

            if ('visibility' == $status_type) {
                if (!defined('PRESSPERMIT_PRO_VERSION')) :?>

                <?php elseif (!defined('PRESSPERMIT_STATUSES_VERSION')) :?>
                    <div class="pp-statuses-config-notice">
                    <?php
                    printf(
                        // translators: %1$s and %2$s is link markup
                        esc_html__('For custom Visibility Statuses, please %1$senable the Status Control module%2$s of Permissions Pro.', 'publishpress-statuses'),
                        '<a href="' . esc_url(admin_url('admin.php?page=presspermit-settings&pp_tab=modules')) . '">',
                        '</a>'
                    );
                    ?>
                    </div>

                <?php elseif (!get_option('presspermit_privacy_statuses_enabled')) :?>
                    <div class="pp-statuses-config-notice">
                    <?php
                    if (defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
                        $url = admin_url('admin.php?page=pp-capabilities-settings&pp_tab=capabilities');
                    } else {
                        $url = admin_url('admin.php?page=presspermit-settings&pp_tab=statuses');
                    }

                    printf(
                        // translators: %1$s and %2$s is link markup
                        esc_html__('Note: Custom Visibility Statuses are %1$sdisabled%2$s.', 'publishpress-permissions'),
                        '<a href="' . esc_url($url) . '">',
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
                
                <?php 
                do_action('publishpress_statuses_table_tabs', $status_type);
                ?>
            </div>
            <?php
            
            require_once(__DIR__ . '/StatusListTable.php');
            $wp_list_table = new \PublishPress_Statuses\StatusListTable();
            $wp_list_table->prepare_items();

            do_action('publishpress_statuses_list_table_init', $status_type, $wp_list_table);
            ?>

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
            $status_type = \PublishPress_Functions::REQUEST_key('status_type');

			if (('visibility' == $status_type) && ! defined('PRESSPERMIT_STATUSES_VERSION')) {
                return;
            }

        	if (!$title = apply_filters('publishpress_statuses_management_title', '', $status_type)) {
        		$title = __('Add New Pre-Publication Status', 'publishpress-statuses');
        	}
        
        	if (!$descript = apply_filters('publishpress_statuses_management_descript', '', $status_type)) {
        		$descript = __('This status can be assigned to an unpublished post using the Post Status dropdown.', 'publishpress-statuses');
        	}

            \PublishPress\ModuleAdminUI_Base::instance()->module->title = $title;
            \PublishPress\ModuleAdminUI_Base::instance()->default_header($descript);

            $enable_left_col = true;

        /** Status Settings **/
        } elseif (isset($plugin_page) && ('publishpress-statuses-settings' === $plugin_page)) {
            \PublishPress\ModuleAdminUI_Base::instance()->module->title = __('PublishPress Statuses Settings', 'publishpress-statuses');
            \PublishPress\ModuleAdminUI_Base::instance()->default_header();

            $enable_left_col = true;
        }

        if (!empty($enable_left_col)) :?>
            <div class="pp-columns-wrapper pp-enable-sidebar">
            <div class='pp-column-left pp-statuses-col-left'>
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
            
            <?php if ('publdishpress-statuses' != $plugin_page) :
                do_action('publishpress_statuses_settings_sidebar'); 
            endif; ?>

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
