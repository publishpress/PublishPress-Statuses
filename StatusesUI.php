<?php
namespace PublishPress_Statuses;

// Custom Status management: Statuses Screen
class StatusesUI {
    function __construct() {
        add_filter('presspermit_edit_status_default_tab', [$this, 'fltEditStatusDefaultTab']);

        // Register our settings
        add_action('admin_init', [$this, 'register_settings']);

        $this->loadAdminMessages();

        // trigger 
        do_action('publishpress_plugin_screen', \PublishPress_Statuses::instance());
    }

    private function loadAdminMessages() {
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
                __('Status order:', 'publishpress-statuses'),
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

        echo sprintf(
            '<input type="hidden" name="%s" value="0" />',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[moderation_statuses_default_by_sequence]'
        ) . ' ';

        echo sprintf(
            '<input type="checkbox" name="%s" value="on" autocomplete="off" %s>',
            esc_attr(\PublishPress_Statuses::SETTINGS_SLUG) . '[moderation_statuses_default_by_sequence]',
            $module->options->moderation_statuses_default_by_sequence ? 'checked' : ''
        ) . ' ';

        esc_html_e('Publish button defaults to next status in workflow (instead of highest permitted)', 'publishpress-statuses');

        echo '</div>';
    }

    /**
     * Choose the post types that should be displayed on the calendar
     *
     * @since 0.7
     */
    public function settings_post_types_option()
    {
        \PublishPress_Statuses::instance()->helper_option_custom_post_type();
    }

    public function fltEditStatusDefaultTab($default_tab) {
        if (!$default_tab = \PublishPress_Functions::REQUEST_key('pp_tab')) {
            $default_tab = 'name';
        }

        return $default_tab;
    }

    // placeholder
    public function render_dashboard_page()
    {
        \PublishPress\ModuleAdminUI_Base::instance()->default_header();

        $page = '';
        if (isset($_REQUEST['page'])) {
            $page = sanitize_text_field($_REQUEST['page']);
        }
    }

    /**
     * Primary configuration page for custom status class.
     * Shows form to add new custom statuses on the left and a
     * WP_List_Table with the custom status terms on the right
     */
    //public function print_configure_view()
    public function render_admin_page($custom_status)
    {
        \PublishPress\ModuleAdminUI_Base::instance()->default_header(__('Define custom statuses. Drag to re-order, nest, or move to a different workflow.', 'publishpress-statuses'));

        $page = '';
        if (isset($_REQUEST['page'])) {
            $page = sanitize_text_field($_REQUEST['page']);
        }

        /** Full width view for editing a custom status **/
        if (isset($_GET['action'], $_GET['name']) && $_GET['action'] == 'edit-status'): ?>
            <?php
            require_once(__DIR__ . '/StatusEditUI.php');
            \PublishPress_Statuses\StatusEditUI::display();
        else: 
        ?>
            <h3 class='nav-tab-wrapper'>
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
            </h3>

            <?php
            if (empty($_GET['action']) || ('statuses' == $_GET['action'])) :
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
                        if (isset($_GET['action']) && $_GET['action'] == 'add-new'): ?>
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
                        elseif (isset($_GET['action']) && ('options' == $_GET['action'])) : ?>
                            <form class='basic-settings'
                                    action="<?php
                                    echo esc_url(
                                        \PublishPress_Statuses::getLink(['action' => 'options'])
                                        // \PublishPress_Statuses::getLink(['action' => 'change-options'])
                                    ); ?>"
                                    method='post'>
                                <br/>
                                <p><?php
                                    echo __(
                                        'Note: Post types can also be specified for each individual status.',
                                        'publishpress-statuses'
                                    ); 
                                    ?>
                                </p>
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
