<?php
namespace PublishPress_Statuses;

class PostEditClassicSubmitMetabox
{
    public static function post_submit_meta_box($post, $args = [])
    {
        // @todo: move moderation_statuses setup into separate function that can be called by Ajax 'pp_get_selectable_statuses'

        $is_administrator = \PublishPress_Statuses::isContentAdministrator();
        $type_obj = get_post_type_object($post->post_type);

        $post_status = apply_filters('presspermit_editor_ui_status', $post->post_status, $post, $args);

        if ('auto-draft' == $post_status)
            $post_status = 'draft';

        if (!$post_status_obj = get_post_status_object($post_status)) {
            $post_status_obj = get_post_status_object('draft');
        }

        $moderation_statuses = Admin::get_selectable_statuses($post, $args);

        $can_publish = current_user_can($type_obj->cap->publish_posts);

        $_args = compact('is_administrator', 'type_obj', 'post_status_obj', 'can_publish', 'moderation_statuses');
        $_args = array_merge($args, $_args);  // in case args passed into metabox are needed within static calls in the future
        ?>
        <div class="submitbox" id="submitpost">

            <div id="minor-publishing">
                <div id="minor-publishing-actions">
                    <div id="save-action">
                        <?php self::post_save_button($post, $_args); ?>
                    </div>
                    <div id="preview-action">
                        <?php self::post_preview_button($post, $_args); ?>
                    </div>
                    <div class="clear"></div>
                </div><?php // minor-publishing-actions ?>

                <div id="misc-publishing-actions">
                    <div class="misc-pub-section misc-pub-post-status">
                        <?php self::post_status_display($post, $_args); ?>
                    </div>

                    <?php do_action('pp_statuses_post_submitbox_misc_sections', $post, $_args); ?>

                    <?php
                    if (!empty($args['args']['revisions_count'])) :
                        $revisions_to_keep = wp_revisions_to_keep($post);
                        ?>
                        <div class="misc-pub-section num-revisions">
                            <?php
                            if ($revisions_to_keep > 0 && $revisions_to_keep <= $args['args']['revisions_count']) {
                                echo '<span title="' . esc_attr(sprintf(__('Your site is configured to keep only the last %s revisions.'),
                                        number_format_i18n($revisions_to_keep))) . '">';
                                printf(esc_html__('Revisions: %s'), '<b>' . (int) number_format_i18n($args['args']['revisions_count']) . '+</b>');
                                echo '</span>';
                            } else {
                                printf(esc_html__('Revisions: %s'), '<b>' . (int) number_format_i18n($args['args']['revisions_count']) . '</b>');
                            }
                            ?>
                            <a class="hide-if-no-js"
                               href="<?php echo esc_url(get_edit_post_link($args['args']['revision_id'])); ?>"><?php _ex('Browse', 'revisions'); ?></a>
                        </div>
                    <?php
                    endif;
                    ?>

                    <?php
                    if ($can_publish) : // Contributors don't get to choose the date of publish
                        ?>
                        <div class="misc-pub-section curtime misc-pub-section-last">
                            <?php self::post_time_display($post, $_args); ?>
                        </div>
                    <?php endif; ?>

                    <?php do_action('post_submitbox_misc_actions', $post); ?>
                </div> <?php // misc-publishing-actions ?>

                <div class="clear"></div>
            </div> <?php // minor-publishing ?>

            <div id="major-publishing-actions">
                <?php do_action('post_submitbox_start', $post); ?>
                <div id="delete-action">
                    <?php // PP: no change from WP core
                    if (current_user_can("delete_post", $post->ID)) {
                        if (!EMPTY_TRASH_DAYS)
                            $delete_text = \PublishPress_Statuses::__wp('Delete Permanently');
                        else
                            $delete_text = \PublishPress_Statuses::__wp('Move to Trash');
                        ?>
                        <a class="submitdelete deletion"
                           href="<?php echo get_delete_post_link($post->ID); ?>"><?php echo esc_html($delete_text); ?></a><?php
                    } ?>
                </div>

                <div id="publishing-action">
                    <?php self::post_publish_ui($post, $_args); ?>
                </div>
                <div class="clear"></div>
            </div> <?php // major-publishing-actions ?>

        </div> <?php // submitpost ?>

        <?php

    } // end function post_submit_meta_box()

    /*
     *  Classic Editor Post Submit Metabox: Post Save Button HTML
     */
    public static function post_save_button($post, $args)
    {
        $post_status_obj = $args['post_status_obj'];
        ?>
        <?php
        // @todo: confirm we don't need a hidden save button when current status is private */
        if (!$post_status_obj->public && !$post_status_obj->private && !$post_status_obj->moderation && ('future' != $post_status_obj->name)) :
            if (!empty($post_status_obj->labels->update)) {
                $save_as = $post_status_obj->labels->update;
            } else {
                $post_status_obj = get_post_status_object('draft');
                $save_as = $post_status_obj->labels->save_as;
            }
            ?>
            <input type="submit" name="save" id="save-post" value="<?php echo esc_attr($save_as) ?>"
                   tabindex="4" class="button button-highlighted"/>
        <?php elseif ($post_status_obj->moderation) :
            if (apply_filters('presspermit_display_save_as_button', true, $post, $args)):?>
            <input type="submit" name="save" id="save-post" value="<?php echo esc_attr($post_status_obj->labels->save_as) ?>"
                   tabindex="4" class="button button-highlighted"/>
            <?php 
            endif;
            ?>
        <?php else : ?>
            <input type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save'); ?>"
                   class="button button-highlighted" style="display:none"/>
        <?php endif; ?>

        <span class="spinner" style="margin:2px 2px 0"></span>
        <?php
    }

    /**
     *  Classic Editor Post Submit Metabox: Post Preview Button HTML
     */
    public static function post_preview_button($post, $args)
    {
        if (empty($args['post_status_obj'])) return;

        if ($type_obj = get_post_type_object($post->post_type)) {
            if (empty($type_obj->public) && empty($type_obj->publicly_queryable)) {
                return;
            }
        }

        $post_status_obj = $args['post_status_obj'];
        ?>
        <?php
        if ($post_status_obj->public) {
            $preview_link = esc_url(get_permalink($post->ID));
            $preview_button = \PublishPress_Statuses::__wp('Preview Changes');
            $preview_title = '';
        } else {
            $preview_link = esc_url(apply_filters(
                'preview_post_link', 
                add_query_arg('preview', 'true', get_permalink($post->ID)),
                $post
            ));
            
            $preview_button = apply_filters('presspermit_preview_post_label', \PublishPress_Statuses::__wp('Preview'));
            $preview_title = apply_filters('presspermit_preview_post_title', '');
        }
        ?>
        <a class="preview button" href="<?php echo esc_url($preview_link); ?>" target="wp-preview" id="post-preview"
           tabindex="4" title="<?php echo esc_attr($preview_title);?>"><?php echo esc_html($preview_button); ?></a>
        <input type="hidden" name="wp-preview" id="wp-preview" value=""/>
        <?php
    }

    /**
     *  Classic Editor Post Submit Metabox: Post Status Dropdown HTML
     */
    public static function post_status_display($post, $args)
    {
        $defaults = ['post_status_obj' => false, 'can_publish' => false, 'moderation_statuses' => []];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        if (!isset($moderation_statuses['draft'])) {
            $moderation_statuses = array_merge(
                ['draft' => get_post_status_object('draft')], 
                $moderation_statuses
            );
        }
        ?>
        <label for="post_status"><?php echo esc_html(\PublishPress_Statuses::__wp('Status:')); ?></label>
        <?php
        $post_status = $post_status_obj->name;
        ?>
        <span id="post-status-display">
        <?php
        if ($post_status_obj->private)
            echo esc_html(\PublishPress_Statuses::__wp('Privately Published'));
        elseif ($post_status_obj->public)
            echo esc_html(\PublishPress_Statuses::__wp('Published'));
        elseif (!empty($post_status_obj->labels->caption))
            echo esc_html($post_status_obj->labels->caption);
        else
            echo esc_html($post_status_obj->label);
        ?>
        </span>&nbsp;
        <?php

        // multiple moderation stati are selectable or a single non-current moderation stati is selectable
        $select_moderation = (count($moderation_statuses) > 1 || ($post_status != key($moderation_statuses)));

        if ($post_status_obj->public || $post_status_obj->private || $can_publish || $select_moderation) { ?>
            <a href="#post_status"
            <?php if ($post_status_obj->private || ($post_status_obj->public && 'publish' != $post_status)) { ?>style="display:none;"
            <?php } ?>class="edit-post-status hide-if-no-js" tabindex='4'><?php echo esc_html(\PublishPress_Statuses::__wp('Edit')) ?></a>
            <?php
            if (current_user_can('pp_create_groups')) :
                $url = admin_url("admin.php?page=presspermit-groups");
                ?>
                <span style="float:right; margin-top: -5px;">
                <a href="<?php echo esc_url($url); ?>" class="visibility-customize pp-submitbox-customize" target="_blank">
                <span class="dashicons dashicons-groups" title="<?php esc_attr_e('Define Permission Groups'); ?>" alt="<?php esc_attr_e('groups', 'presspermit');?>"></span>
                </a>
            </span>
            <?php endif; ?>

            <div id="post-status-select" class="hide-if-js">
                <input type="hidden" name="hidden_post_status" id="hidden_post_status"
                    value="<?php echo esc_attr($post_status); ?>"/>
                <select name='post_status' id='post_status' tabindex='4' autocomplete='off'>

                    <?php if ($post_status_obj->public || $post_status_obj->private || ('future' == $post_status)) : ?>
                        <option <?php selected(true, true); ?> value='publish'>
                        <?php echo esc_html($post_status_obj->labels->caption) ?>
                        </option>
                    <?php endif; ?>

                    <?php
                    foreach ($moderation_statuses as $_status => $_status_obj) : ?>
                        <option <?php selected($post_status, $_status); ?> value='<?php echo esc_attr($_status) ?>'>
                        <?php 
                        $caption = (!empty($_status_obj->status_parent) && !empty($moderation_statuses[$_status_obj->status_parent])) 
                        ? '— ' . $_status_obj->labels->caption
                        : $_status_obj->labels->caption;

                        echo esc_html($caption);
                        ?>
                        </option>
                    <?php endforeach ?>

                </select>
                <a href="#post_status" class="save-post-status hide-if-no-js button"><?php echo esc_html(\PublishPress_Statuses::__wp('OK')); ?></a>
                <a href="#post_status" class="pp-cancel-post-status hide-if-no-js"><?php echo esc_html(\PublishPress_Statuses::__wp('Cancel')); ?></a>
                <?php
                if (('draft' == $post_status_obj->name || $post_status_obj->moderation) 
                && (current_user_can('pp_define_post_status') || current_user_can('pp_define_moderation'))
                ) {
                    $url = admin_url('admin.php?action=add-new&page=publishpress-statuses');
                    echo "<br /><a href='" . esc_url($url) . "' class='pp-postsubmit-add-moderation' target='_blank'>" . esc_html__('add workflow status', 'publishpress-statuses') . '</a>';
                }
                ?>
            </div>

        <?php } // endif status editable
    }

    /**
     *  Classic Editor Post Submit Metabox: Post Time Display HTML
     */
    public static function post_time_display($post, $args)
    {
        global $action;

        if (empty($args['post_status_obj'])) return;

        if (!defined('PRESSPERMIT_MIN_DATE_STRING')) {
            define('PRESSPERMIT_MIN_DATE_STRING', '0000-00-00 00:00:00');
        }

        $post_status_obj = $args['post_status_obj'];
        ?>
        <span id="timestamp">
        <?php
        // translators: Publish box date formt, see http://php.net/date
        $datef = \PublishPress_Statuses::__wp('M j, Y @ G:i');

        if (0 != $post->ID) {
            $published_stati = get_post_stati(['public' => true, 'private' => true], 'names', 'or');
            $date = date_i18n($datef, strtotime($post->post_date));

            if ('future' == $post_status_obj->name) { // scheduled for publishing at a future date
                printf(esc_html__('Scheduled for: %s%s%s'), '<strong>', esc_html($date), '</strong>');

            } elseif (in_array($post_status_obj->name, $published_stati)) { // already published
                printf(esc_html__('Published on: %s%s%s'), '<strong>', esc_html($date), '</strong>');

            } elseif (in_array($post->post_date_gmt, [constant('PRESSPERMIT_MIN_DATE_STRING'), '0000-00-00 00:00:00'])) { // draft, 1 or more saves, no date specified
                echo apply_filters(
                    'presspermit_post_editor_immediate_caption', 
                    sprintf(
                        esc_html__('Publish %simmediately%s'),
                        '<strong>',
                        '</strong>'
                    ),
                    $post
                );

            } elseif (time() < strtotime($post->post_date_gmt . ' +0000')) { // draft, 1 or more saves, future date specified
                printf(esc_html__('Schedule for: %s%s%s'), '<strong>', esc_html($date), '</strong>');

            } else { // draft, 1 or more saves, date specified
                printf(esc_html__('Publish on: %s%s%s'), '<strong>', esc_html($date), '</strong>');
            }
        } else { // draft (no saves, and thus no date specified)
            printf(
                esc_html__('Publish %simmediately%s'),
                '<strong>',
                '</strong>'
            );
        }
        ?></span>
        <a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" tabindex='4'><?php echo esc_html(\PublishPress_Statuses::__wp('Edit')) ?></a>
        <div id="timestampdiv" class="hide-if-js"><?php touch_time(($action == 'edit'), 1, 4); ?></div>
        <?php
    }

    /**
     *  Classic Editor Post Submit Metabox: Post Publish Button HTML
     */
    public static function post_publish_ui($post, $args)
    {
        $defaults = ['post_status_obj' => false, 'can_publish' => false, 'moderation_statuses' => []];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }
        ?>
        <span class="spinner"></span>

        <?php
        if ((!$post_status_obj->public && !$post_status_obj->private && ('future' != $post_status_obj->name))) {
            $status_obj = \PublishPress_Statuses::defaultStatusProgression($post);

            if (!empty($status_obj->public) || !empty($status_obj->private)) :
                if (!empty($post->post_date_gmt) && time() < strtotime($post->post_date_gmt . ' +0000')) :
                    $future_status_obj = get_post_status_object('future');
                    ?>
                    <input name="original_publish" type="hidden" id="original_publish" value="<?php echo esc_attr($future_status_obj->labels->publish) ?>"/>
                    <input name="publish" type="submit" class="button button-primary button-large" id="publish" tabindex="5" accesskey="p" value="<?php echo esc_attr($future_status_obj->labels->publish) ?>"/>
                <?php
                else :
                    $publish_status_obj = get_post_status_object('publish');
                    ?>
                    <input name="original_publish" type="hidden" id="original_publish" value="<?php echo esc_attr($publish_status_obj->labels->publish) ?>"/>
                    <input name="publish" type="submit" class="button button-primary button-large" id="publish" tabindex="5" accesskey="p" value="<?php echo esc_attr($publish_status_obj->labels->publish) ?>"/>
                <?php
                endif;
            else :
                echo '<input name="pp_submission_status" type="hidden" id="pp_submission_status" value="' . esc_attr($status_obj->name) . '" />';
                ?>
                <input name="original_publish" type="hidden" id="original_publish" value="<?php echo esc_attr($status_obj->labels->publish) ?>"/>
                <input name="publish" type="submit" class="button button-primary button-large" id="publish" tabindex="5" accesskey="p" value="<?php echo esc_attr($status_obj->labels->publish) ?>"/>
            <?php
            endif;
        } else { ?>
            <input name="original_publish" type="hidden" id="original_publish" value="<?php echo esc_attr(\PublishPress_Statuses::__wp('Update')); ?>"/>
            <input name="save" type="submit" class="button button-primary button-large" id="publish" tabindex="5" accesskey="p" value="<?php echo esc_attr(\PublishPress_Statuses::__wp('Update')); ?>"/>
            <?php
        }
    }
}