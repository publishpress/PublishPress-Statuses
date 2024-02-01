<?php
namespace PublishPress_Statuses;

// @ todo: merge this with PostsListing

// Implement Custom Statuses on the Posts screen and in Post Editor
class PostEdit
{
    function __construct() {
        // NOTE: 'pp_custom_status_list' filter is applied by class PostEditStatus
        add_filter('pp_custom_status_list', [$this, 'flt_publishpress_status_list'], 50, 2);

        if (!in_array(\PublishPress_Functions::findPostType(), ['forum', 'topic', 'reply'])) {
            // Gutenberg scripts are only loaded if Gutenberg-specific actions fire.
            require_once(__DIR__ . '/PostEditGutenberg.php');
            new PostEditGutenberg();

            // Always load basic scripts for Classic Editor support unless explicitly disabled by plugin setting
            if ('gutenberg' !== \PublishPress_Statuses::instance()->options->force_editor_detection) {
                add_action('add_meta_boxes', [$this, 'act_replace_publish_metabox'], 10, 2);

                add_action('admin_print_scripts', [$this, 'act_classic_editor_failsafe'], 100);

                add_action('wp_loaded', function() {
                    // Load full set of Classic Editor scripts if Gutenberg is not detected, or if Classic Editor explicitly specified by plugin setting
                    if (!\PublishPress_Functions::isBlockEditorActive() 
                    || ('classic' == \PublishPress_Statuses::instance()->options->force_editor_detection)) {
                        require_once(__DIR__ . '/PostEditClassic.php');
                        new PostEditClassic();
                    }
                });
            }
        }
    }

    public function post_submit_meta_box($post, $args = [])
    {
        require_once(__DIR__ . '/PostEditClassicSubmitMetabox.php');
        PostEditClassicSubmitMetabox::post_submit_meta_box($post, $args);
    }

    public function act_replace_publish_metabox($post_type, $post)
    {
        global $wp_meta_boxes;

        if ('attachment' != $post_type) {
            if (!empty($wp_meta_boxes[$post_type]['side']['core']['submitdiv'])) {
                // Classic Editor: override WP submit metabox with a compatible equivalent (applying the same hooks as core post_submit_meta_box()

                if (!empty($post)) {
                    if (\PublishPress_Statuses::isUnknownStatus($post->post_status)
                    || \PublishPress_Statuses::isPostBlacklisted($post->ID)
                    ) {
                        return;
                    }
                }

                // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                $wp_meta_boxes[$post_type]['side']['core']['submitdiv']['callback'] = [$this, 'post_submit_meta_box'];
            }
        }
    }

    function act_classic_editor_failsafe() {
        global $post;

        if (empty($post) || defined('PUBLISHPRESS_STATUSES_DISABLE_CLASSIC_FAILSAFE')) {
            return;
        }

        if (\PublishPress_Statuses::isUnknownStatus($post->post_status)
        || \PublishPress_Statuses::isPostBlacklisted($post->ID)
        ) {
            return;
        }

        $moderation_statuses = Admin::get_selectable_statuses($post, []);

        if (!$custom_statuses = array_diff_key($moderation_statuses, array_fill_keys(['draft', 'pending', 'future', 'auto-draft', 'publish', 'private'], true))) {
            return;
        }

        $current_status_obj = get_post_status_object($post->post_status); 
        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function ($) {
        var intStatusesFailsafe = setInterval(() => {
            if (!$('#poststuff').length) {
                return;
            }

            clearInterval(intStatusesFailsafe);

            if (!$('#misc-publishing-actions').length || !$('select#post_status').length || $('#pp_statuses_ui_rendered').length) {
                return;
            }

            <?php
            foreach ($custom_statuses as $post_status => $status_obj)
            :?> if (!$('#post-status-select option [value="<?php echo esc_attr($post_status);?>"]').length) {
                    $('select#post_status').append('<option value="<?php echo esc_attr($post_status);?>"><?php echo esc_html($status_obj->label);?></option>');
                }
            <?php endforeach;?>

            <?php if (isset($custom_statuses[$post->post_status]))
            :?> $('#post-status-select').val('<?php echo esc_attr($post->post_status);?>');
                $('#post-status-display').html('<?php echo esc_html($current_status_obj->label);?>');
            <?php endif;?>
        }, 100);
        });
        /* ]]> */
        </script>
        <?php
    }

    /**
     * Enqueue Javascript resources that we need in the admin:
     * - Primary use of Javascript is to manipulate the post status dropdown on Edit Post and Manage Posts
     * - jQuery Sortable plugin is used for drag and dropping custom statuses
     * - We have other custom code for JS niceties
     */

    // This is currently loaded by Admin.php

    public function action_admin_enqueue_scripts()
    {
        global $pagenow;

        if (\PublishPress_Statuses::DisabledForPostType()) {
            return;
        }

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
                    'publishpress-custom_status',
                    PUBLISHPRESS_STATUSES_URL . 'common/custom-status-classic-editor.css',
                    false,
                    PUBLISHPRESS_STATUSES_VERSION,
                    'all'
                );
            }
        }
    }

    // Filter the statuses included in Gutenberg editor status dropdown. 
    //
    // Note: PressPermit customization of per-type status availability, ordering and branch relationships (Permissions > Post Statuses)
    // will be applied even if permissions filtering is disabled for the post type.
    public static function flt_publishpress_status_list($status_terms, $post, $args = [])
    {
        if (!$post || !is_object($post) || empty($post->ID)) {
            if ($post_id = \PublishPress_Functions::getPostID()) {
                $post = get_post($post_id);
            }
        }

        if (!$post) {
            if ($rest_post_type = defined('REST_REQUEST') && apply_filters('presspermit_rest_post_type', '')) {
                $post_type = $rest_post_type;
                $post_status = 'draft';
            } else {
                return $status_terms;
            }
        } else {
            $post_type = $post->post_type;
            $post_status = $post->post_status;
        }

        if ('auto-draft' == $post_status)
            $post_status = 'draft';

        if (!$post_status_obj = get_post_status_object($post_status)) {
            $post_status_obj = get_post_status_object('draft');
        }

        $status_slug = (!empty($post_status_obj->slug)) ? $post_status_obj->slug : $post_status_obj->name;

        $all_moderation_statuses = \PublishPress_Statuses::getPostStati(['moderation' => true, 'internal' => false], 'object');

        $moderation_statuses = \PublishPress_Statuses::getPostStati(['moderation' => true, 'internal' => false, 'post_type' => $post_type], 'object');
        unset($moderation_statuses['future']);

        // Only filter moderation statuses
        $other_statuses = [];

        if (empty($moderation_statuses[$post_status])) {
            // for Gutenberg, always include original post status in dropdown
            $other_statuses[$post_status] = true;
        }

        foreach ($status_terms as $key => $status_term) {
            if (!empty($status_term->slug)) {
	            if (!isset($all_moderation_statuses[$status_term->slug]) && ('draft' != $status_term->slug) && !empty($status_term->slug)) {
	                $other_statuses[$status_term->slug] = true;
	            }
	        }
        }

        // If PressPermit permissions filtering is not enabled for this post type, don't impose access limits.
        // Note, though that status ordering and workflow branch relationships are still applied
        //if (self::isPostTypeEnabled()) { @todo: check post type enable (for entire custom statuses feature)
            if (!\PublishPress_Statuses::isContentAdministrator()) {
                $moderation_statuses = \PublishPress_Statuses::filterAvailablePostStatuses($moderation_statuses, $post_type, $post_status);
            }

            $moderation_statuses = apply_filters('presspermit_available_moderation_statuses', $moderation_statuses, $moderation_statuses, $post);
        //}

        // Don't exclude the current status, regardless of other arguments
        $_args = ['include_status' => $status_slug];

        if (!empty($post_status_obj->status_parent)) {
            if (defined('PRESSPERMIT_RESTRICT_WORKFLOW_BRANCH_SELECTION')) { // legacy behavior < v2.8.8
                // If current status is a workflow branch child, only offer other statuses in that branch
                $_args['status_parent'] = $post_status_obj->status_parent;
            } else {
                // If current status is a workflow branch child, also offer other statuses in that branch
                if ($status_children = \PublishPress_Statuses::getStatusChildren($post_status_obj->status_parent, $moderation_statuses)) {
                    $moderation_statuses = array_merge($moderation_statuses, $status_children);
                }
            }
        } elseif ($status_children = \PublishPress_Statuses::getStatusChildren($status_slug, $moderation_statuses)) {
            if (defined('PRESSPERMIT_RESTRICT_WORKFLOW_BRANCH_SELECTION')) { // legacy behavior < v2.8.8
                // If current status is a workflow branch parent, only offer other statuses in that branch
                $moderation_statuses = array_merge([$status_slug => $post_status_obj], $status_children);
            } else {
                // If current status is a workflow branch parent, also offer other statuses in that branch
                $moderation_statuses = array_merge($moderation_statuses, $status_children);
            }
        } else {
            // If current status is in main workflow with no branch children, only display other main workflow statuses 
            $_args['status_parent'] = '';
        }

        $moderation_statuses = \PublishPress_Statuses::orderStatuses($moderation_statuses, $_args);

        $type_obj = get_post_type_object($post_type);
        $can_publish = ($type_obj) ? current_user_can($type_obj->cap->publish_posts) : false;

        $return = [];

        foreach (array_merge(['draft' => true], $moderation_statuses, $other_statuses) as $status => $status_obj) {
            $found = false;

            foreach ($status_terms as $status_term) {
                if (!empty($status_term->slug) && ($status_term->slug == $status)) {
                    if (('pending' == $status) && \PublishPress_Functions::isBlockEditorActive()) {
                        $status_obj = get_post_status_object('pending');
                        $status_label = (empty($status_obj)) ? $status_obj->label : esc_html__('Pending Review', 'publishpress-statuses');

                        // Alternate item to allow use of "Save as Pending" button
                        //
                        // This will allow different behavior from the Submit button, 
                        // which may default to next/highest available workflow status.
                        $return[]= (object)['name' => '_pending', 'label' => $status_label];
                    }

                    $return[]= $status_term;

                    $found = true;
                    break;
                }
            }

            if (!$found && is_object($status_obj)) {
                // don't insert statues which PublilshPress excluded if status has default capabilities
                if (!empty($status_obj->capability_status)) {
                    $return[]= (object)['slug' => $status_obj->name, 'name' => $status_obj->name, 'label' => $status_obj->label];
                }
            }
        }

        return $return;
    }
}
