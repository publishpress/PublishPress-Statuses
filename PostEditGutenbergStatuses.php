<?php

namespace PublishPress_Statuses;

// This class is only used with PublishPress inactive. It supplies the Post Status dropdown (for custom types).
class PostEditGutenbergStatuses
{
    public static function loadBlockEditorStatusGuidance() 
    {
        if ($post_id = \PublishPress_Functions::getPostID()) {
            if (defined('PUBLISHPRESS_REVISIONS_VERSION') && rvy_in_revision_workflow($post_id)) {
                return;
            }
        }

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
        
        wp_enqueue_script('publishpress-statuses-post-block-edit', PUBLISHPRESS_STATUSES_URL . "common/js/post-block-edit{$suffix}.js", ['jquery', 'jquery-form'], PUBLISHPRESS_STATUSES_VERSION, true);

        if (!defined('PP_STATUSES_ALLOW_PREPUBLISH_DISABLE')) {
            wp_enqueue_style('publishpress-statuses-post-block-edit', PUBLISHPRESS_STATUSES_URL . '/common/css/post-block-edit.css', [], PUBLISHPRESS_STATUSES_VERSION);
        }
        
        $current_status = get_post_field('post_status', $post_id);

        if (in_array($current_status, ['', 'auto-draft'])) {
            $current_status = 'draft';
        }

        $current_status_obj = get_post_status_object($current_status);

        if ($current_status_obj && (!empty($current_status_obj->public) || !empty($current_status_obj->private))) {
            $next_status_obj = $current_status_obj;
        } else {
            $next_status_obj = \PublishPress_Statuses::defaultStatusProgression($post_id);
        }

        if ($args['workflowSequence'] = \PublishPress_Statuses::instance()->options->moderation_statuses_default_by_sequence) {
            $default_by_sequence = true;
            $max_status_obj = \PublishPress_Statuses::defaultStatusProgression($post_id, ['default_by_sequence' => false, 'skip_current_status_check' => true]);

            $args['advanceStatus'] = esc_html__('Advance Status', 'publishpress-statuses');
        } else {
            $max_status_obj = $next_status_obj;
            $args['advanceStatus'] = '';
        }

        if (($current_status == $next_status_obj->name) || ( (!empty($current_status_obj->public) || !empty($current_status_obj->private)) && (!empty($next_status_obj->public) || !empty($next_status_obj->private)))) {
            if (!empty($next_status_obj->public) || !empty($next_status_obj->private)) {
                $publish_label = esc_html(\PublishPress_Statuses::__wp('Save'));
                $save_as_label = $publish_label;
            } else {
                $publish_label = $next_status_obj->labels->save_as;
            }
        } else {
            // secondary safeguard to ensure a valid button label
            if (!empty($next_status_obj->labels->publish)) {
                $publish_label = ($args['advanceStatus']) ? $args['advanceStatus'] : $next_status_obj->labels->publish;
            } elseif (!empty($next_status_obj->labels->save_as)) {
                $publish_label = ($args['advanceStatus']) ? $args['advanceStatus'] : $next_status_obj->labels->save_as;
            } else {
                $publish_label = esc_html__('Advance Status', 'publishpress-statuses');  // fallback will not happen if statuses properly defined
            }
        }

        $args['update'] = esc_html(\PublishPress_Statuses::__wp('Save'));

        if (!isset($save_as_label)) {
            if ((!empty($next_status_obj->labels->publish))) {
                $save_as_label = (!empty($default_by_sequence)) ? esc_html__('Advance Status', 'publishpress-statuses') : $next_status_obj->labels->publish;
            } else {
                $save_as_label = $args['update'];
            }
        }

        // Now that logic is done, pass actual Next Status object even if not defaulting to it
        if (!$args['workflowSequence'] && !defined('PP_STATUSES_SUPPRESS_NEXT_STATUS_SELECTION')) {
            $next_status_obj = \PublishPress_Statuses::defaultStatusProgression(0, ['default_by_sequence' => true]);
        }

        $args = array_merge($args, ['publish' => $publish_label, 'saveAs' => $save_as_label, 'nextStatus' => $next_status_obj->name, 'maxStatus' => $max_status_obj->name, 'defaultBySequence' => !empty($default_by_sequence)]);

        if (!$is_administrator = \PublishPress_Statuses::isContentAdministrator()) {
            $post_type = \PublishPress_Functions::findPostType();

            $current_status = get_post_field('post_status', $post_id);

            foreach (\PublishPress_Statuses::getPostStati(['moderation' => true, 'post_type' => $post_type]) as $status) {
                if (($status != $current_status) && \PublishPress_Statuses::haveStatusPermission('set_status', $post_type, $status)) {
                    $args = apply_filters('publishpress_statuses_block_editor_args', $args, compact(['post_id', 'post_type', 'status']));
                }
            }

            if ($type_obj = get_post_type_object($post_type)) {
                $status_obj = get_post_status_object($current_status);

                $is_published = !empty($status_obj) && (!empty($status_obj->public) || !empty($status_obj->private));

                $can_publish = (!$is_published && current_user_can($type_obj->cap->publish_posts)) || current_user_can($type_obj->cap->edit_published_posts);

                if (!$can_publish) {
                    $can_publish = apply_filters(
                        'publishpress_statuses_can_publish', 
                        $can_publish, 
                        compact(['post_id', 'is_published', 'post_type'])
                    );
                }
            } else {
                $can_publish = false;
            }
        }

        if (!defined('PRESSPERMIT_NO_PREPUBLISH_RECAPTION')) {
            $args['prePublish'] = apply_filters('presspermit_workflow_button_label', __('Workflow', 'publishpress-statuses'), $post_id);
        }

        $args['saveDraftCaption'] = esc_html(\PublishPress_Statuses::__wp('Save Draft')); // this is used for reference in js
        $args['submitRevisionCaption'] = esc_html__('Submit Revision', 'publishpress-statuses'); // identify Revisions caption, to avoid overriding it

        $args['disableRecaption'] = defined('PRESSPERMIT_EDITOR_NO_RECAPTION');

        $args['hidePending'] = \PublishPress_Statuses::instance()->options->pending_status_regulation && !current_user_can('status_change_pending');

        $args['parentLabel'] = __('Parent');
        $args['publishLabel'] = __('Publish');

        $args['moveParentUI'] = defined('PP_STATUSES_MOVE_PARENT_UI');

        $args['timezoneOffset'] = get_option('gmt_offset');

        global $wp_version;
        
        $args['isGutenbergLegacy'] = ! ((version_compare($wp_version, '6.6', '>=') && !defined('GUTENBERG_VERSION')) || (defined('GUTENBERG_VERSION') && version_compare(GUTENBERG_VERSION, '18.5', '>=')));

        wp_localize_script('publishpress-statuses-post-block-edit', 'ppObjEdit', $args);
    }
}

