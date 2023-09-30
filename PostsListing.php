<?php
namespace PublishPress_Statuses;

// Implement Custom Statuses on the Posts screen and in Post Editor
class PostsListing
{
    var $post_ids = [];

    function __construct() {
        // Hook to add the status column to Manage Posts
        add_filter('manage_posts_columns', [$this, '_filter_manage_posts_columns']);

        // We need these for pages (http://core.trac.wordpress.org/browser/tags/3.3.1/wp-admin/includes/class-wp-posts-list-table.php#L283)
        add_filter('manage_pages_columns', [$this, '_filter_manage_posts_columns']);

        add_action('admin_head', [$this, 'actApplyPendingCaptionJS']);

        add_action('admin_print_footer_scripts', [$this, 'act_modify_inline_edit_ui']);

        add_filter('display_post_states', [$this, 'fltDisplayPostStates']);

        add_action('manage_posts_custom_column', [$this, 'flt_manage_posts_custom_column']);
        add_action('manage_pages_custom_column', [$this, 'flt_manage_posts_custom_column']);
        
        add_action('plugins_loaded', function() {
            add_filter('views_' . \PublishPress_Functions::findPostType(), [$this, 'flt_views_stati']);
        });

        add_action('the_post', [$this, 'act_log_displayed_posts']);
    }

    function act_log_displayed_posts($_post)
    {
        $this->post_ids[] = $_post->ID;
    }

    /**
     * Insert new column header for post status after the title column
     *
     * @param array $posts_columns Columns currently shown on the Edit Posts screen
     *
     * @return array Same array as the input array with a "status" column added after the "title" column
     */
    public function _filter_manage_posts_columns($posts_columns)
    {
        // Return immediately if the supplied parameter isn't an array (which shouldn't happen in practice?)
        // http://wordpress.org/support/topic/plugin-publishpress-bug-shows-2-drafts-when-there-are-none-leads-to-error-messages
        if (! is_array($posts_columns)) {
            return $posts_columns;
        }

        // Only do it for the post types this module is activated for
        if (! in_array(\PublishPress_Statuses::getCurrentPostType(), \PublishPress_Statuses::getEnabledPostTypes())) {
            return $posts_columns;
        }

        $result = [];
        foreach ($posts_columns as $key => $value) {
            if ($key == 'title') {
                $result[$key] = $value;
                $result['status'] = __('Status', 'publishpress-statuses');
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    function flt_manage_posts_custom_column($column_name)
    {
        if ($column_name == 'status') {
            global $post;

            if ($status_obj = get_post_status_object($post->post_status)) {
                if (!empty($status_obj->label)) {
                    echo $status_obj->label;
                }
            }
        }
    }

    // If Pending status label is customized, apply it to Posts listing
    // @todo: js file with localize_script()
    function actApplyPendingCaptionJS() {
        $label_changes = [];

        foreach(['pending' => esc_html__('Pending')] as $status => $default_label) { // support label changes to multiple statuses
            $status_obj = get_post_status_object($status);

            if ($status_obj && ($status_obj->label != $default_label)) {
                $label_changes[$status]= (object)['old_label' => $default_label, 'new_label' => $status_obj->label];
            }
        }

        if (!$label_changes) {
            return;
        }
        ?>
        <style type="text/css">
            span.post-state{display:none;}
        </style>

        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function ($) {
                <?php foreach($label_changes as $status => $obj):?>
                $("span.post-state:contains('<?php echo esc_attr($obj->old_label); ?>')").html('<?php echo esc_attr($obj->new_label); ?>');
                $("select[name='_status'] option[value='<?php echo esc_attr($status); ?>']").html('<?php echo esc_attr($obj->new_label); ?>');
                $("td.column-status:contains('<?php echo esc_attr($obj->old_label); ?>')").html('<?php echo esc_attr($obj->new_label); ?>'); // PublishPress status column
                <?php endforeach;?>

                $("span.post-state").show();
            });
            /* ]]> */
        </script>

        <?php
    }

    // @todo: move to .js
    // add "keep" checkboxes for custom private stati; set checked based on current or scheduled post status
    // add conditions UI to inline edit
    function act_modify_inline_edit_ui()
    {
        global $current_user;
        global $typenow;

        $screen = get_current_screen();
        $post_type_object = get_post_type_object($screen->post_type);
        ?>
        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function ($) {
                <?php
                $isContentAdministrator = current_user_can('administrator') 
                || current_user_can('pp_administer_content') 
                || (is_multisite() && is_super_admin());

                $type_obj = get_post_type_object($screen->post_type);

                $args = ['post_type' => $screen->post_type, 'post_status' => 'draft'];
                $moderation_statuses = Admin::get_selectable_statuses(false, $args);

                $can_publish = current_user_can($type_obj->cap->publish_posts);

                // @todo: ordered, indented statuses in quick edit dropdown
                ?>
                $('select[name="_status"]').html('');
                <?php
                if ($can_publish) {
                    $moderation_statuses = array_merge(
                        ['publish' => get_post_status_object('publish')], 
                        $moderation_statuses
                    );
                }

                foreach ($moderation_statuses as $_status => $_status_obj) :
                    $html = '<option value="' . esc_attr($_status) . '">';

                    $caption = (!empty($_status_obj->status_parent) && !empty($moderation_statuses[$_status_obj->status_parent])) 
                    ? '— ' . $_status_obj->labels->caption
                    : $_status_obj->labels->caption;

                    $html .= esc_html($caption) . '</option>';
                ?>
                    $('select[name="_status"]').append('<?php echo $html;?>');
                <?php endforeach;?> 
            });
            //]]>
        </script>
        <?php
    } // end function modify_inline_edit_ui

    // status display in Edit Posts table rows
    public static function fltDisplayPostStates($post_states)
    {
        global $post, $wp_post_statuses;

        if (empty($post) || in_array($post->post_status, ['publish', 'private', 'pending', 'draft']))
            return $post_states;

        if ('future' == $post->post_status) {  // also display eventual visibility of scheduled post (if non-public)
            if ($scheduled_status = get_post_meta($post->ID, '_scheduled_status', true)) {
                if ('publish' != $scheduled_status) {
                    if ($_scheduled_status_obj = get_post_status_object($scheduled_status))
                        $post_states[] = $_scheduled_status_obj->label;
                }
            }
        } elseif (\PublishPress_Functions::empty_REQUEST('post_status') 
        || (\PublishPress_Functions::REQUEST_key('post_status') != $post->post_status)
        ) {  // if filtering for this status, don't display caption in result rows
            $status_obj = (!empty($wp_post_statuses[$post->post_status])) ? $wp_post_statuses[$post->post_status] : false;
            if ($status_obj) {
                if ($status_obj->private || (!empty($status_obj->moderation)))
                    $post_states[] = $status_obj->label;
            }
        }

        return $post_states;
    }

    function flt_views_stati($views)
    {
        $post_type = \PublishPress_Functions::findPostType();
        $type_stati = \PublishPress_Statuses::getPostStati(['show_in_admin_all_list' => true, 'post_type' => $post_type]);

        $views = array_intersect_key($views, array_flip($type_stati));

        // also remove filtered stati from "All" count 
        $num_posts = array_intersect_key(wp_count_posts($post_type, 'readable'), $type_stati);

        $total_posts = array_sum((array)$num_posts);

        $class = !isset($views['mine']) && \PublishPress_Functions::empty_REQUEST('post_status', 'show_sticky') ? ' class="current"' : '';
        $allposts = (strpos($views['all'], 'all_posts=1')) ? $allposts = '&all_posts=1' : '';

        $views['all'] = "<a href='edit.php?post_type=$post_type{$allposts}'$class>" 
        . sprintf(
            _nx(
                'All <span class="count">(%s)</span>', 
                'All <span class="count">(%s)</span>', 
                (int) $total_posts, 
                'posts'
            ), 
            number_format_i18n($total_posts)
        ) 
        . '</a>';

        return $views;
    }
}
