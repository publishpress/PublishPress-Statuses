<?php
namespace PublishPress_Statuses;

// Custom Status management: Status Edit UI
class StatusEditUI
{
    public static function display() {

// Check whether the term exists
$name = sanitize_text_field($_GET['name']);
$status = \PublishPress_Statuses::getStatusBy('id', $name);
if (! $status) {
    echo '<div class="error"><p>' . $module->messages['status-missing'] . '</p></div>';

    return;
}

$edit_status_link = \PublishPress_Statuses::getLink(['action' => 'edit-status', 'name' => $name]);

$label = (isset($_POST['label'])) ? sanitize_text_field($_POST['label']) : $status->label;
$description = (isset($_POST['description'])) ? sanitize_textarea_field($_POST['description'])
    : $status->description;
$color = (isset($_POST['color'])) ? sanitize_text_field($_POST['color']) : $status->color;
$icon = (isset($_POST['icon'])) ? sanitize_text_field($_POST['icon']) : $status->icon;
$icon = str_replace('dashicons|', '', $icon);

echo "<ul class='nav-tab-wrapper' style='margin-bottom:-0.1em'>";

$class_selected = "nav-tab nav-tab-active";
$class_unselected = "nav-tab";

$tabs = ['name' => __('Name', 'publishpress')];

if (empty($status->publish) && empty($status->private) && !in_array($name, ['draft', 'future', 'publish'])) {
    $tabs['labels'] = __('Labels', 'publishpress');

    $tabs['roles'] = __('Roles', 'publishpress');

    if ('pending' != $name) {
        $tabs['post_types'] = __('Post Types', 'publishpress');
    }
}

$tabs = apply_filters('publishpress_statuses_edit_status_tabs', $tabs, $status->name);

$pp_tab = (!empty($_REQUEST['pp_tab'])) ? sanitize_key($_REQUEST['pp_tab']) : 'name';

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
});
/* ]]> */
</script>

<div id="ajax-response"></div>
<form method="post" action="<?php
echo esc_attr($edit_status_link); ?>">
    <input type="hidden" name="name" value="<?php
    echo esc_attr($name); ?>"/>
    <?php
    wp_original_referer_field();
    wp_nonce_field('edit-status');
    
    self::mainTabContent(
        compact(['name', 'label', 'description', 'color', 'icon']),
        $default_tab
    );

    //require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/StatusAdmin.php');
    //new \PublishPress\Permissions\Statuses\UI\StatusAdmin();

    //$default_tab = apply_filters('presspermit_edit_status_default_tab', 'name');

    self::tabContent('labels', $status, $default_tab);
    self::tabContent('roles', $status, $default_tab);
    self::tabContent('post_types', $status, $default_tab);

    do_action('publishpress_statuses_edit_status_tab_content', $status, $default_tab);
    ?>

    <p class="submit">
        <input type="hidden" name="page" value="publishpress-statuses" />
        <input type="hidden" name="action" value="edit-status" />
        <?php
        if (!empty($_REQUEST['return_module'])) :?>
            <input type="hidden" name="return_module" value="<?php echo esc_attr($_REQUEST['return_module']);?>" />
        <?php endif;

        submit_button(__('Update Status', 'publishpress'), 'primary pp-statuses', 'submit', false); ?>
        <a class="cancel-settings-link"
            href="<?php
            echo esc_url(\PublishPress_Statuses::getLink()); ?>"><?php
            _e('Cancel', 'publishpress'); ?></a>
    </p>
</form>

<?php
    } // end function displayUI

    public static function mainTabContent($args = [], $default_tab = 'name') {
        foreach(
            ['name', 'label', 'description', 'color', 'icon'] as $field
        ) {
            $$field = (!empty($args[$field])) ? $args[$field] : '';
        }

        $display = ($default_tab == 'name') ? '' : 'display:none';
        ?>
        <table class="form-table" style="<?php echo esc_attr($display);?>">
            <tr class="form-field form-required">
                <th scope="row" valign="top"><label for="label"><?php
                        _e(
                            'Status Label',
                            'publishpress'
                        ); ?></label></th>
                <td><input name="status_label" id="label"
                            type="text" <?php

                    $status_obj = get_post_status_object($name);
                    if (!empty($status_obj) && !empty($status_obj->_builtin)) : echo 'disabled="disabled"';
                    endif; ?> value="<?php
                    echo esc_attr($label); ?>" size="40" aria-required="true"/>
                    <?php
                    \PublishPress_Statuses\StatusesUI::printErrorOrDescription(
                        'label',
                        __(
                            'The name is used to identify the status. (Max: 20 characters)',
                            'publishpress'
                        )
                    ); ?>
                </td>
            </tr>

            <?php if (!empty($name)):?>
            <tr class="form-field">
                <th scope="row" valign="top"><?php
                    _e('Slug', 'publishpress'); ?></th>
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
                            'publishpress'
                        )
                    ); ?>
                </td>
            </tr>
            <?php endif;?>

            <tr class="form-field">
                <th scope="row" valign="top"><label for="description"><?php
                        _e(
                            'Description',
                            'publishpress'
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
                            'publishpress'
                        )
                    ); ?>
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="color"><?php
                        _e(
                            'Color',
                            'publishpress'
                        ); ?></label></th>
                <td>

                    <?php
                    echo \PublishPress_Statuses\StatusesUI::colorPicker(esc_attr($color), 'status_color') ?>

                    <?php
                    \PublishPress_Statuses\StatusesUI::printErrorOrDescription(
                        'color',
                        __('The color is used to identify the status.', 'publishpress')
                    ); ?>
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="icon"><?php
                        _e('Icon', 'publishpress'); ?></label>
                </th>
                <td>
                    <input class="regular-text" type="hidden" id="status_icon" name="icon"
                            value="<?php
                            if (isset($icon)) {
                                echo esc_attr($icon);
                            } ?>"/>

                    <div id="icon_picker_wrap" data-target='#status_icon'
                            data-preview="#icon_picker_preview" class="button dashicons-picker">
                        <div id="icon_picker_preview" class="dashicons <?php
                        echo isset($icon) ? esc_attr($icon) : ''; ?>"></div>
                        <div class="icon_picker_button_label"><?php
                            echo __('Select Icon', 'publishpress'); ?></div>
                    </div>

                    <?php
                    \PublishPress_Statuses\StatusesUI::printErrorOrDescription(
                        'status_icon',
                        __('The icon is used to visually represent the status.', 'publishpress')
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
        
        //$status = $status->slug;
        $status_obj = $status; // get_post_status_object($status);
        $status_types = (!empty($status_obj) && !empty($status_obj->post_type)) ? $status_obj->post_type : [];

        $label_disabled = ('future' == $status) ? ' disabled ' : '';

        echo "<div id='pp-" . esc_attr($tab) . "' style='clear:both;margin:0;style='" . esc_attr($display) . "' class='pp-options'>";
        //do_action("presspermit_" . esc_attr($tab) . "_options_pre_ui");
        echo "<table class='" . esc_attr($table_class) . "' id='pp-" . esc_attr($tab) . "_table' style='" . esc_attr($display) . "'>";

        switch ($tab) {
            case 'roles' :
                $roles = \PublishPress_Functions::getRoles(true);
                ?>
                <tr class="form-field">
                    <th><label for="status_assign"><?php esc_html_e('Assign Status', 'presspermit-pro') ?></label></th>

                    <td class="set-status-roles">
                        <?php foreach($roles as $role_name => $role_label):
                            if (\PublishPress_Functions::isEditableRole($role_name)) :
                                $role = get_role($role_name);
                                $cap_name = str_replace('-', '_', "status_change_{$status->name}");

                                $is_administrator = !empty($role->capabilities['administrator']) || !empty($role->capabilities['manage_options']);
                                $can_set_status = $is_administrator || !empty($role->capabilities[$cap_name]);
                        ?>
                                <div>
                                <input type="hidden" name="roles_set_status[<?php echo $role_name;?>]" value="<?php echo ($is_administrator) ? 1 : 0?>" />

                                <label>
                                <input type="checkbox" name="roles_set_status[<?php echo $role_name;?>]" id="roles_set_status" autocomplete="off"
                                <?php checked($can_set_status);?> <?php disabled($is_administrator);?> value="1" class="regular-text" />
                                <?php echo $role_label;?>
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
                <th><label for="status_label"><?php esc_html_e('Post Types', 'presspermit-pro') ?></label></th>
                <td>

                <?php
                $types = get_post_types(['public' => true, 'show_ui' => true], 'object', 'or');

                //$omit_types = apply_filters('presspermit_unfiltered_post_types', ['wp_block']);
                $omit_types = ['nav_menu', 'attachment', 'revision', 'wp_navigation', 'wp_block']; // @todo: review block, navigation filtering

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
                            <?php esc_html_e('(All Types)', 'presspermit-pro'); ?>
                        </label>
                    </div>
                    <?php

                    $hint = '';

                    if (!$locked_status) {
                        $disabled = ($all_enabled) ? ' disabled ' : '';

                        // @todo: migrate PublishPress setting to enable / disable all custom statuses per-type ?
                        /*
                        if ((defined('PUBLISHPRESS_VERSION') && class_exists('PP_Custom_Status')) && defined('PRESSPERMIT_COLLAB_VERSION') && !empty($status_obj->pp_custom)) {
                            if (!empty($publishpress->modules->custom_status->options->post_types)) {
                                $types = array_intersect_key($types, array_intersect($publishpress->modules->custom_status->options->post_types, ['on']));

                                $display_hint = true;
                            }
                        }
                        */

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
                        } // end foreach src_otype
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
                        <th><label for="status_save_as_label"><?php esc_html_e('Save As Label', 'presspermit-pro') ?></label></th>
                        <td><input type="text" name="status_save_as_label" id="status_save_as_label" autocomplete="off"
                                value="<?php echo esc_attr(stripslashes($save_as_label)); ?>" class="regular-text"  /></td>
                    </tr>
                    <?php
                    $button_label = (!empty($status_obj) && !empty($status_obj->labels->publish)) ? $status_obj->labels->publish : '';
                    ?>
                    <tr class="form-field">
                        <th><label for="status_publish_label"><?php esc_html_e('Submit Button Label', 'presspermit-pro') ?></label></th>
                        <td><input type="text" name="status_publish_label" id="status_publish_label" autocomplete="off"
                                value="<?php echo esc_attr(stripslashes($button_label)); ?>" class="regular-text"  /></td>
                    </tr>
                <?php endif;
                break;
        }

        echo '</table></div>';
    }

} // end class
