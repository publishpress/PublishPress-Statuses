<?php
namespace PublishPress_Statuses;

class PostEditClassic
{
    function __construct() {
        // Classic Editor support
        //
        // This script executes on the 'init' action if is_admin() and $pagenow is 'post-new.php' or 'post.php' and the block editor is not active.
        //

        add_action('add_meta_boxes', [$this, 'act_comments_metabox'], 10, 2);
        add_action('add_meta_boxes', [$this, 'act_replace_publish_metabox'], 10, 2);

        add_action('admin_head', [$this, 'act_object_edit_scripts'], 99);  // needs to load after post.js to unbind handlers
        
        //add_action('admin_print_footer_scripts', [$this, 'act_supplement_js_captions'], 99);
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
                $wp_meta_boxes[$post_type]['side']['core']['submitdiv']['callback'] = [$this, 'post_submit_meta_box'];
            }
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

        $draft_obj = get_post_status_object('draft');

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

        wp_enqueue_script(
            'publishpress-statuses-object-edit', 
            PUBLISHPRESS_STATUSES_URL . "common/js/object-edit{$suffix}.js", 
            ['jquery', 'jquery-form'], 
            PUBLISHPRESS_STATUSES_VERSION, 
            true
        );

        $args = [
            'pubStati' => json_encode($stati['public']),
            'pvtStati' => json_encode($stati['private']),
            'modStati' => json_encode($stati['moderation']),
            'draftSaveAs' => $draft_obj->labels->save_as,
            'nowCaption' => esc_html__('Current Time', 'presspermit-pro'),
            'update' => esc_html__('Update'),
            'schedule' => esc_html__('Schedule'),
            'published' => esc_html__('Published'),
            'privatelyPublished' => esc_html__('Privately Published'),
            'publish' => esc_html__('Publish'),
            'publishSticky' => esc_html__('Published, Sticky')
        ];

        if (!empty($post)) {
            if (\PublishPress_Statuses::instance()->options->moderation_statuses_default_by_sequence) {
                $next_status_obj = \PublishPress_Statuses::getNextStatusObject(
                    $post->ID, 
                    ['default_by_sequence' => true, 'post_status' => $post->post_status]
                );

                if ($next_status_obj && !in_array($next_status_obj->name, ['publish', 'private'])) {
                    if (!empty($next_status_obj->labels->publish)) {
                        $args['publish'] = $next_status_obj->labels->publish;
                    } elseif (!empty($next_status_obj->labels->save_as)) {
                        $args['publish'] = $next_status_obj->labels->save_as;
                    } else {
                        $args['publish'] = sprintf(__('Submit as %s', 'publishpress-statuses'), $next_status_obj->label);
                    }
                }
            }
        }


        wp_localize_script('publishpress-statuses-object-edit', 'ppObjEdit', $args);

        global $wp_scripts;
        $wp_scripts->in_footer [] = 'publishpress-statuses-object-edit';  // otherwise it will not be printed in footer (@todo review)
    }

    // ensure Comments metabox for custom published / private stati
    public function act_comments_metabox($post_type, $post)
    {
        global $wp_meta_boxes;
        if (isset($wp_meta_boxes[$post_type]['normal']['core']['commentsdiv']))
            return;

        if ($post_status_obj = get_post_status_object($post->post_status)) {
            if (('publish' == $post->post_status || 'private' == $post->post_status) 
            && post_type_supports($post_type, 'comments')
            ) {
                add_meta_box('commentsdiv', \PublishPress_Statuses::__wp('Comments'), 'post_comment_meta_box', $post_type, 'normal', 'core');
            }
        }
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
