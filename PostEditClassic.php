<?php
namespace PublishPress_Statuses;

class PostEditClassic
{
    function __construct() {
        // Classic Editor support
        //
        // This script executes on the 'init' action if is_admin() and $pagenow is 'post-new.php' or 'post.php' and the block editor is not active.
        //

        add_action('admin_print_scripts', [$this, 'post_admin_header']);
        add_action('admin_head', [$this, 'act_object_edit_scripts'], 99);  // needs to load after post.js to unbind handlers
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

        if (\PublishPress_Statuses\Admin::is_post_management_page()) {
            $post_type_obj = get_post_type_object(\PublishPress_Statuses::getCurrentPostType());
            $custom_statuses = \PublishPress_Statuses::getPostStati([], 'object');  // @todo: confirm inclusion of core statuses here
            $selected = null;
            $selected_name = __('Draft', 'publishpress-statuses');

            $custom_statuses = apply_filters('pp_custom_status_list', $custom_statuses, $post);

            // Only add the script to Edit Post and Edit Page pages -- don't want to bog down the rest of the admin with unnecessary javascript
            if (! empty($post)) {
                //get raw post so custom post status is included
                $_post = get_post($post);

                // Get the status of the current post
                if ($_post->ID == 0 || $_post->post_status == 'auto-draft' || $pagenow == 'edit.php') {
                    // TODO: check to make sure that the default exists
                    $selected = \PublishPress_Statuses::DEFAULT_STATUS;
                } else {
                    $selected = $_post->post_status;
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
                var label_save = '<?php echo esc_html__('Save'); ?>';
                var pp_default_custom_status = '<?php echo esc_js(\PublishPress_Statuses::DEFAULT_STATUS); ?>';
                var current_status = '<?php echo esc_js($selected); ?>';
                var current_status_name = '<?php echo esc_js($selected_name); ?>';
                var custom_statuses = <?php echo wp_json_encode($all_statuses); ?>;
                var current_user_can_publish_posts = <?php if (current_user_can($post_type_obj->cap->publish_posts)) echo '1'; else echo '0'; ?>;
                var current_user_can_edit_published_posts = <?php if (current_user_can($post_type_obj->cap->edit_published_posts)) echo '1'; else echo '0'; ?>;
            </script>

            <style type="text/css">
            a.pp-custom-moderation-promo {display: none;}
            </style>
            <?php
        }
    }

    public function act_object_edit_scripts()
    {
        global $typenow, $post;

        $stati = [];
        foreach (['public', 'private', 'moderation'] as $prop) {
            foreach (\PublishPress_Statuses::getPostStati([$prop => true, 'post_type' => $typenow], 'object') as $status => $status_obj) {
	            // Safeguard: Fall back on native WP object if our copy was corrupted. 
	            // @todo: confirm this is not needed once Class Editor status caption refresh issues are resolved.
	            if (empty($status_obj->labels->name)) {
	                $status_obj = get_post_status_object($status);
                }

                $stati[$prop][] = [
                    'name' => $status, 
                    'label' => $status_obj->labels->name, 
                    'save_as' => isset($status_obj->labels->save_as) ? $status_obj->labels->save_as : '',
                    'publish' => isset($status_obj->labels->publish) ? $status_obj->labels->publish : ''
                ];
            }
        }

        if ($default_by_sequence = \PublishPress_Statuses::instance()->options->moderation_statuses_default_by_sequence) {
            $is_administrator = \PublishPress_Statuses::isContentAdministrator();

            $post_status = ($post) ? $post->post_status : 'draft';

            if (!$post_status_obj = get_post_status_object($post_status)) {
                $post_status_obj = get_post_status_object('draft');
            }

            if ($is_administrator && $default_by_sequence && empty($post_status_obj->public) && empty($post_status_obj->private) && ('future' != $post_status)) {
                $stati['moderation'][] = [
                    'name' => '_public',
                    'label' => __('Published', 'publishpress-statuses'),
                    'save_as' => __('Publish', 'publishpress-statuses'),
                    'publish' => __('Advance Status', 'publishpress-statuses'),
                ];
            }
        }

        $draft_obj = get_post_status_object('draft');

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

        wp_enqueue_script(
            'publishpress-statuses-classic-edit', 
            PUBLISHPRESS_STATUSES_URL . "common/js/classic-edit{$suffix}.js", 
            ['jquery', 'jquery-form'], 
            PUBLISHPRESS_STATUSES_VERSION, 
            true
        );

        $args = [
            'pubStati' => wp_json_encode($stati['public']),
            'pvtStati' => wp_json_encode($stati['private']),
            'modStati' => wp_json_encode($stati['moderation']),
            'draftSaveAs' => $draft_obj->labels->save_as,
            'nowCaption' => esc_html__('Current Time', 'publishpress-statuses'),
            'update' => esc_html__('Update'),
            'schedule' => esc_html__('Schedule'),
            'published' => esc_html__('Published'),
            'privatelyPublished' => esc_html__('Privately Published'),
            'publish' => esc_html__('Publish'),
            'publishSticky' => esc_html__('Published, Sticky'),
            'defaultBySequence' => $default_by_sequence
        ];

        if (!empty($post)) {
            $next_status_obj = \PublishPress_Statuses::getNextStatusObject(
                $post->ID, 
                ['default_by_sequence' => $default_by_sequence, 'post_status' => $post->post_status]
            );

            if ($next_status_obj && !in_array($next_status_obj->name, ['publish', 'private', 'future'])) {
                if (!empty($next_status_obj->labels->publish)) {
                    $args['publish'] = $next_status_obj->labels->publish;
                } elseif (!empty($next_status_obj->labels->save_as)) {
                    $args['publish'] = $next_status_obj->labels->save_as;
                } else {
                    $args['publish'] = sprintf(__('Submit as %s', 'publishpress-statuses'), $next_status_obj->label);
                }

                $args['schedule'] = $args['publish'];
            }

            // support "bypass sequence" toggle in classic editor UI
            if ($default_by_sequence) {
                $args['nextPublish'] = $args['publish'];
                $args['nextSchedule'] = $args['schedule'];

                $max_status_obj = \PublishPress_Statuses::getNextStatusObject(
                    $post->ID,
                    ['default_by_sequence' => false, 'post_status' => $post->post_status]
                );

                if (in_array($max_status_obj->name, ['publish', 'future'])) {
                    $args['maxPublish'] = esc_html__('Publish');
                    $args['maxSchedule'] = esc_html__('Schedule');
                } else {
                    if (!empty($max_status_obj->labels->publish)) {
                        $args['maxPublish'] = $max_status_obj->labels->publish;
                    } elseif (!empty($max_status_obj->labels->save_as)) {
                        $args['maxPublish'] = $max_status_obj->labels->save_as;
                    } else {
                        $args['maxPublish'] = sprintf(__('Submit as %s', 'publishpress-statuses'), $max_status_obj->label);
                    }

                    $args['maxSchedule'] = $args['maxPublish'];
                }
            }
        }

        wp_localize_script('publishpress-statuses-classic-edit', 'ppObjEdit', $args);

        global $wp_scripts;
        $wp_scripts->in_footer [] = 'publishpress-statuses-classic-edit';  // otherwise it will not be printed in footer (@todo review)
    }

    // @todo: confirm this is obsolete
    public function act_supplement_js_captions()
    {
        global $typenow, $wp_scripts;

        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
            var postL10n;

        if (typeof (postL10n) != 'undefined') {
            <?php foreach( 
                array_merge(
                    \PublishPress_Statuses::getPostStati(['public' => true, 'post_type' => $typenow], 'object'),
                    \PublishPress_Statuses::getPostStati(['private' => true, 'post_type' => $typenow], 'object')
                ) as $_status => $_status_obj 
            ) {
                if ( !in_array($_status, ['auto-draft', 'publish']) ) :
                ?>
                postL10n['<?php echo esc_attr($_status); ?>'] = '<?php echo esc_html($_status_obj->labels->visibility); ?>';
                postL10n['<?php echo esc_attr($_status);?>Sticky'] = '<?php printf(esc_html__('%s, Sticky'), esc_html($_status_obj->label)); ?>';
                <?php endif;?>
                <?php
            } // end foreach
            ?>
        }
        /* ]]> */
        </script>
        <?php
    } // end function
}
