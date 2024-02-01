<?php
namespace PublishPress_Statuses;

// Custom Status management: Status Edit UI
class StatusEditUI
{
    public static function display() {

        // Check whether the term exists
        $name = \PublishPress_Functions::REQUEST_key('name');

        if (!$status = \PublishPress_Statuses::getStatusBy('id', $name)) {
            echo '<div class="error"><p>' . esc_html($module->messages['status-missing']) . '</p></div>';
            return;
        }

        $url_args = ['action' => 'statuses'];

        if ($status_type = \PublishPress_Functions::REQUEST_key('status_type')) {
            $url_args['status_type'] = $status_type;
        }

        $url = \PublishPress_Statuses::getLink($url_args);
        ?>
        <div class='pp-edit-status-back'>
            <a href="<?php echo esc_url($url); ?>"><?php esc_html_e('Back to Statuses', 'publishpress-statuses'); ?></a>
        </div>
        <?php

        $edit_status_link = \PublishPress_Statuses::getLink(['action' => 'edit-status', 'name' => $name]);

        $status->icon = str_replace('dashicons|', '', $status->icon);

        echo "<ul class='nav-tab-wrapper' style='margin-bottom:-0.1em'>";

        $class_selected = "nav-tab nav-tab-active";
        $class_unselected = "nav-tab";

        $tabs = ['name' => \PublishPress_Statuses::__wp('Name')];

        if (empty($status->publish) && !in_array($name, ['draft', 'future', 'publish', 'private'])) {
            if (empty($status->private)) {
                $label_storage = \PublishPress_Statuses::instance()->options->label_storage;

                switch ($label_storage) {
                    case 'user':
                        if (empty($status->pp_builtin) && empty($status->_builtin) && !in_array($status->name, ['draft', 'pending', 'future', 'publish', 'private'])){
                            $tabs['labels'] = __('Labels', 'publishpress-statuses');
                        }

                        break;

                    default:
                        if ((empty($status->_builtin) || ('pending' == $status->name))
                        && !in_array($status->name, ['draft', 'publish', 'private', 'future'])
                        ) {
                            $tabs['labels'] = __('Labels', 'publishpress-statuses');
                        }
                }
            }
                                          // Custom Visibility statuses do not currently support type-agnostic "status_change_" capabilities
                                          // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
            if ((empty($status->private) /*|| (class_exists('\PublishPress\StatusCapabilities') && \PublishPress\StatusCapabilities::postStatusHasCustomCaps($status->name))*/)
            && (('pending' != $name) || \PublishPress_Statuses::instance()->options->pending_status_regulation)
            ) {
                $tabs['roles'] = __('Roles', 'publishpress-statuses');
            }

            if ('pending' != $name) {
                $tabs['post_types'] = __('Post Types', 'publishpress-statuses');
            }
        }

        $tabs = apply_filters('publishpress_statuses_edit_status_tabs', $tabs, $status->name);

        $pp_tab = (!\PublishPress_Functions::empty_REQUEST('pp_tab')) ? \PublishPress_Functions::REQUEST_key('pp_tab') : 'name';

        $default_tab = apply_filters('presspermit_edit_status_default_tab', $pp_tab);

        if (!in_array($default_tab, array_keys($tabs))) {
            $default_tab = 'name';
        }

        foreach ($tabs as $tab => $caption) {
            $class = ($default_tab == $tab) ? $class_selected : $class_unselected;  // todo: return to last tab

            echo "<li class='" . esc_attr($class) . "'><a href='#pp-" . esc_attr($tab) . "'>"
                . esc_html($caption) . '</a></li>';
        }

        echo '</ul>';
        ?>

        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function ($) {
            // Tabs
            var $tabsWrapper = $('.publishpress-admin ul.nav-tab-wrapper');
            $tabsWrapper.find('li').click(function (e) {
                e.preventDefault();
                $tabsWrapper.children('li').filter('.nav-tab-active').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

                $('.publishpress-admin table.form-table').hide();

                var panel = $(this).find('a').first().attr('href');

                if ('#pp-name' == panel) {
                    $('div.publishpress-admin form table').show();
                    $('div.publishpress-admin form table.pp-statuses-options').hide();
                } else {
                    $(panel).show();
                    $(panel).find('table').show();
                }
            });

            // If the basic set status cap is changed on the Roles tab, mirror on Post Access tab and in type-specific Set caps
            $('#pp-roles_table td.set-status-roles input').on('click', function() {
                $('#pp-post_access input[name="' + $(this).attr('name') + '"]').prop('checked', $(this).prop('checked')).next('table').find('td.post-cap input').prop('disabled', !$(this).prop('checked')).prop('checked', $(this).prop('checked'));
            });

            // If the basic set status cap is changed on the Post Access tab, mirror on Roles tab and in type-specific Set caps
            $('#pp-post_access input.cme_status_set_basic').on('click', function() {
                $('#pp-roles_table td.set-status-roles input[name="' + $(this).attr('name') + '"]').prop('checked', $(this).prop('checked'));
                $(this).next('table').find('tbody tr td.post-cap label input').prop('checked', $(this).prop('checked'));
            });

            // Work around status capabilities library bug (displaying Set capability checkbox for disabled post types)
            var basic_status_set_cap = $('#pp-post_access input.cme_status_set_basic').attr('title');
            $('#pp-post_access td.post-cap input[title="' + basic_status_set_cap + '"]').parent().remove();

            $('div.pp-subtext').html('<?php esc_html_e('Enforce type-specific post capabilitities for this status, or share capabilities with another status.', 'publishpress-statuses');?>');
        });
        /* ]]> */
        </script>

        <div id="ajax-response"></div>
        <form method="post" action="<?php
        echo esc_url($edit_status_link); ?>">
            <input type="hidden" name="name" value="<?php
            echo esc_attr($name); ?>"/>
            <?php
            wp_original_referer_field();
            wp_nonce_field('edit-status');
            
            self::mainTabContent(
                array_intersect_key(
                    (array) $status,
                    array_fill_keys(['name', 'label', 'description', 'color', 'icon'], true)
                ),
                $default_tab
            );

            self::tabContent('labels', $status, $default_tab);
            self::tabContent('roles', $status, $default_tab);
            self::tabContent('post_types', $status, $default_tab);

            do_action('publishpress_statuses_edit_status_tab_content', $status, $default_tab);
            ?>

            <p class="submit">
                <input type="hidden" name="page" value="publishpress-statuses" />
                <input type="hidden" name="action" value="edit-status" />
                <input type="hidden" name="pp_tab" value="<?php echo '#pp-' . esc_attr($default_tab);?>" />
                <?php
                if (!\PublishPress_Functions::empty_REQUEST('return_module')) :?>
                    <input type="hidden" name="return_module" value="<?php echo esc_attr(\PublishPress_Functions::REQUEST_key('return_module'));?>" />
                <?php endif;

                submit_button(__('Update Status', 'publishpress-statuses'), 'primary pp-statuses', 'submit', false); ?>
            </p>
        </form>

    <?php
    } // end function display

    public static function mainTabContent($args = [], $default_tab = 'name') {
        foreach(
            ['name', 'label', 'description', 'color', 'icon'] as $field
        ) {
            $$field = (!empty($args[$field])) ? $args[$field] : '';
        }

        $status_obj = get_post_status_object($name);

        $display = ($default_tab == 'name') ? '' : 'display:none';

        if (!empty($status_obj)) {
            $label_storage = \PublishPress_Statuses::instance()->options->label_storage;

            switch ($label_storage) {
                case 'user':
                    if (!empty($status_obj->pp_builtin) || !empty($status_obj->_builtin)
                    || in_array($name, ['draft', 'pending', 'publish', 'private', 'future'])
                    ) {
                        $label_locked = true;
                    }

                    break;

                default:
                    if ((!empty($status_obj->_builtin) && ('pending' != $name))
                    || in_array($name, ['draft', 'publish', 'private', 'future'])
                    ) {
                        $label_locked = true;
                    }
            }
        }
        ?>
        <table class="form-table" style="<?php echo esc_attr($display);?>">
            <tr class="form-field form-required">
                <th scope="row" valign="top"><label for="label"><?php
                        _e(
                            'Status Label',
                            'publishpress-statuses'
                        ); ?></label></th>
                <td><input name="status_label" id="label"
                            type="text" <?php

                    if (!empty($status_obj) && !empty($label_locked)) : echo 'disabled="disabled"';
                    endif; ?> value="<?php
                    echo esc_attr($label); ?>" size="40" aria-required="true"/>
                    <?php
                    \PublishPress_Statuses\StatusesUI::printErrorOrDescription(
                        'label',
                        __(
                            'The name is used to identify the status. (Max: 20 characters)',
                            'publishpress-statuses'
                        )
                    ); ?>
                </td>
            </tr>

            <?php if (!empty($name)):?>
            <tr class="form-field">
                <th scope="row" valign="top"><?php
                    \PublishPress_Statuses::_e_wp('Slug', 'publishpress-statuses'); ?></th>
                <td>
                    <input type="text" name="slug" id="slug" disabled
                            value="<?php
                            echo esc_attr($name); ?>" <?php

                    $status_obj = get_post_status_object($name);
                    if (!empty($status_obj) && !empty($status_obj->_builtin)) : echo 'disabled="disabled"';
                    endif; ?> />
                    <?php
                    \PublishPress_Statuses\StatusesUI::printErrorOrDescription(
                        'slug',
                        __(
                            'The slug is the unique ID for the status and is changed when the name is changed.',
                            'publishpress-statuses'
                        )
                    ); ?>
                </td>
            </tr>
            <?php endif;?>

            <tr class="form-field">
                <th scope="row" valign="top"><label for="description"><?php
                        _e(
                            'Description',
                            'publishpress-statuses'
                        ); ?></label></th>
                <td>
                <textarea name="description" id="description" rows="5"
                            cols="50" style="width: 97%;"><?php
                    
                    echo esc_textarea($description); ?></textarea>
                    <?php
                    \PublishPress_Statuses\StatusesUI::printErrorOrDescription(
                        'description',
                        __(
                            'The description is primarily for administrative use, to give you some context on what the custom status is to be used for.',
                            'publishpress-statuses'
                        )
                    ); ?>
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="color"><?php
                        _e(
                            'Color',
                            'publishpress-statuses'
                        ); ?></label></th>
                <td>

                    <?php
                    \PublishPress_Statuses\StatusesUI::colorPicker(esc_attr($color), 'status_color') ?>

                    <?php
                    \PublishPress_Statuses\StatusesUI::printErrorOrDescription(
                        'color',
                        __('The color is used to identify the status.', 'publishpress-statuses')
                    ); ?>
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="icon"><?php
                        _e('Icon', 'publishpress-statuses'); ?></label>
                </th>
                <td>
                    <input class="regular-text" type="hidden" id="status_icon" name="icon"
                            value="<?php
                            if (isset($icon)) {
                                echo esc_attr($icon);
                            } ?>"/>

                    <div id="publishpress_icon_pick_wrap" data-target='#status_icon'
                            data-preview="#publishpress_icon_pick_preview" class="button dashicons-picker">
                        
                        <div id="publishpress_icon_pick_preview" class="dashicons <?php
                        if (!empty($icon)) echo esc_attr($icon); else echo esc_attr(\PublishPress_Statuses::DEFAULT_ICON); ?>"></div>

                        <div class="publishpress_icon_pick_button_label"><?php
                            esc_html_e('Select Icon', 'publishpress-statuses'); ?></div>
                    </div>

                    <?php
                    \PublishPress_Statuses\StatusesUI::printErrorOrDescription(
                        'status_icon',
                        __('The icon is used to visually represent the status.', 'publishpress-statuses')
                    ); ?>
                </td>
            </tr>
        </table>
        <?php
    }

    private static function tabContent($tab, $status, $default_tab) {
        $display = ($default_tab == $tab) ? '' : 'display:none';
        
        // @todo
        $table_class = 'form-table pp-statuses-options';
        
        $status_obj = $status;
        $status_types = (!empty($status_obj) && !empty($status_obj->post_type)) ? $status_obj->post_type : [];

        $label_disabled = ('future' == $status) ? ' disabled ' : '';

        echo "<div id='pp-" . esc_attr($tab) . "' style='clear:both;margin:0;style='" . esc_attr($display) . "' class='pp-options'>";

        echo "<table class='" . esc_attr($table_class) . "' id='pp-" . esc_attr($tab) . "_table' style='" . esc_attr($display) . "'>";

        switch ($tab) {
            case 'roles' :
                $roles = \PublishPress_Functions::getRoles(true);
                ?>
                <tr class="form-field">
                    <th><label for="status_assign"><?php esc_html_e('Status Availability', 'publishpress-statuses') ?></label>
                    <br /><br />
                    <span class="pp-statuses-field-descript" style="font-weight: normal">
                    <?php esc_html_e('Choose which user roles can assign this status to a post.', 'publishpress-statuses');?>
                    </span>
                    </th>

                    <td class="set-status-roles">
                        <?php foreach($roles as $role_name => $role_label):
                            if (\PublishPress_Functions::isEditableRole($role_name)) :
                                $role = get_role($role_name);
                                $cap_name = str_replace('-', '_', "status_change_{$status->name}");

                                $is_administrator = !empty($role->capabilities['administrator']) || !empty($role->capabilities['manage_options']);
                                $can_set_status = $is_administrator || !empty($role->capabilities[$cap_name]);
                        ?>
                                <div>
                                <input type="hidden" name="roles_set_status[<?php echo esc_attr($role_name);?>]" value="<?php if ($is_administrator) echo '1'; else echo '0';?>" />

                                <label>
                                <input type="checkbox" name="roles_set_status[<?php echo esc_attr($role_name);?>]" id="roles_set_status" autocomplete="off"
                                <?php checked($can_set_status);?> <?php disabled($is_administrator);?> value="1" class="regular-text" />
                                <?php echo esc_html($role_label);?>
                                </label>
                                </div>
                            <?php endif;
                        endforeach;?>
                    </td>
                </tr>
                <?php

                break;
            
            case 'post_types' :
                ?>
                <tr class="form-field">
                <th><label for="status_label"><?php esc_html_e('Post Types', 'publishpress-statuses') ?></label>
                <br /><br />
                <span class="pp-statuses-field-descript" style="font-weight: normal">
                <?php esc_html_e('Choose which post types can be set to this status.', 'publishpress-statuses');?>
                </span>
                </th>
                <td>

                <?php
                $types = get_post_types(['public' => true, 'show_ui' => true], 'object', 'or');

                $omit_types = ['nav_menu', 'attachment', 'revision', 'wp_navigation', 'wp_block']; // @todo: review block, navigation filtering

                $custom_status_post_types = \PublishPress_Statuses::instance()->options->post_types;
                $custom_status_post_types = array_filter($custom_status_post_types);
                $types = array_intersect_key($types, $custom_status_post_types);

                $types = array_diff_key($types, array_fill_keys((array)$omit_types, true));

                $enabled_types = (!empty($status_obj->post_type)) ? $status_obj->post_type : [];

                $option_name = 'pp_status_post_types';

                $enabled = !empty($status_types) ? (array)$status_types : [];
                ?>
                <div>
                    <?php if ($locked_status = in_array($status, ['pending', 'future', 'draft'])) : ?>
                        <input type="hidden" name="<?php echo 'pp_status_all_types'; ?>" value="1"/>
                    <?php
                    endif;

                    $all_enabled = empty($enabled) || $locked_status;
                    $disabled = ($locked_status) ? ' disabled ' : '';
                    ?>

                    <div class="agp-vspaced_input">
                        <label for="<?php echo 'pp_status_all_types'; ?>">
                            <input name="<?php echo 'pp_status_all_types'; ?>" type="checkbox"
                                    id="<?php echo 'pp_status_all_types'; ?>"
                                    value="1" <?php checked('1', $all_enabled);?> <?php echo esc_attr($disabled); ?> />
                            <?php esc_html_e('All Post Types', 'publishpress-statuses'); ?>
                        </label>
                    </div>
                    <?php

                    $hint = '';

                    if (!$locked_status) {
                        $disabled = ($all_enabled) ? ' disabled ' : '';

                        foreach ($types as $key => $obj) {
                            $id = $option_name . '-' . $key;
                            $name = $option_name . "[$key]";
                            ?>
                            <div class="agp-vspaced_input">
                                <label for="<?php echo esc_attr($id); ?>" title="<?php echo esc_attr($key); ?>">
                                    <input name="<?php echo esc_attr($name); ?>" type="hidden" value="0"/>
                                    <input name="<?php echo esc_attr($name); ?>" type="checkbox"
                                        class="pp_status_post_types" <?php echo esc_attr($disabled); ?> id="<?php echo esc_attr($id); ?>"
                                        value="1" <?php checked('1', in_array($key, $enabled, true)); ?> />

                                    <?php
                                    if (isset($obj->labels_pp))
                                        echo esc_html($obj->labels_pp->name);
                                    elseif (isset($obj->labels->name))
                                        echo esc_html($obj->labels->name);
                                    else
                                        echo esc_html($key);
                                ?>
                                </label>
                            </div>
                        <?php
                        }
                    }
                ?>
                </td>
                </div>

                <?php
                break;

            case 'labels' :
                ?>
                <?php if ('future' != $status) :
                    $save_as_label = (!empty($status_obj) && !empty($status_obj->labels->save_as)) ? $status_obj->labels->save_as : '';
                    ?>
                    <tr class="form-field">
                        <th><label for="status_save_as_label"><?php esc_html_e('Save As Label', 'publishpress-statuses') ?></label></th>
                        <td><input type="text" name="status_save_as_label" id="status_save_as_label" autocomplete="off"
                                value="<?php echo esc_attr(stripslashes($save_as_label)); ?>" class="regular-text"  /></td>
                    </tr>
                    <?php
                    $button_label = (!empty($status_obj) && !empty($status_obj->labels->publish)) ? $status_obj->labels->publish : '';
                    ?>
                    <tr class="form-field">
                        <th><label for="status_publish_label"><?php esc_html_e('Submit Button Label', 'publishpress-statuses') ?></label></th>
                        <td><input type="text" name="status_publish_label" id="status_publish_label" autocomplete="off"
                                value="<?php echo esc_attr(stripslashes($button_label)); ?>" class="regular-text"  /></td>
                    </tr>
                <?php endif;
                break;
        }

        echo '</table></div>';
    }

}
