<?php
namespace PublishPress_Statuses;

class PostEditGutenberg
{
    /**
     * Enqueue Gutenberg assets.
     */
    public function actEnqueueBlockEditorAssets()
    {
        global $post, $wp_version;

        if (!empty($post)) {
            if (\PublishPress_Statuses::isUnknownStatus($post->post_status)
            || \PublishPress_Statuses::isPostBlacklisted($post->ID)
            ) {
                return;
            }
        }

        if ($post_id = \PublishPress_Functions::getPostID()) {
            if (defined('PUBLISHPRESS_REVISIONS_VERSION') && !class_exists('PublishPress_Statuses\Revisions') && rvy_in_revision_workflow($post_id)) {
                return;
            }
        }

        $post_type = (!empty($post)) ? $post->post_type : \PublishPress_Statuses::getCurrentPostType();

        if (\PublishPress_Statuses::DisabledForPostType($post_type)) {
            return;
        }

        $status_args = apply_filters('publishpress_statuses_edit_post_status_args', false, $post_id);

        if (!$statuses = $this->getStatuses($status_args)) {
            return;
        }

        // Gutenberg Block Editor support for workflow status progression guidance / limitation
        require_once(__DIR__ . '/PostEditGutenbergStatuses.php');
        PostEditGutenbergStatuses::loadBlockEditorStatusGuidance();

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

        $filename = 
        (version_compare($wp_version, '6.6', '>=') && !defined('GUTENBERG_VERSION')) 
        || (defined('GUTENBERG_VERSION') && version_compare(GUTENBERG_VERSION, '18.5', '>='))
        ? 'custom-status-block' : 'custom-status-block-legacy';

        wp_enqueue_script(
            'publishpress-custom-status-block',
            PUBLISHPRESS_STATUSES_URL . "common/js/{$filename}{$suffix}.js",
            ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-hooks'],
            PUBLISHPRESS_STATUSES_VERSION,
            true
        );

        $custom_privacy_statuses = apply_filters('presspermit_block_editor_privacy_statuses', []);
        $published_statuses = array_merge($custom_privacy_statuses, ['publish', 'private', 'future']);

        $published_status_objects = array_values(
            array_merge(
                get_post_stati(['public' => true, 'private' => 'true'], 'object', 'or'),
                [get_post_status_object('future')]
            )
        );

        $captions = (object) [
            'publicationWorkflow' => __('Publication Workflow', 'publishpress-statuses'),
            'publish' => \PublishPress_Statuses::__wp('Publish'),
            'schedule' => \PublishPress_Statuses::_x_wp('Schedule', 'post action/button label'),
            'advance' => __('Advance Status', 'publishpress-statuses'),
            'postStatus' => __('Post Status', 'publishpress-statuses'),
            // translators: %s is the status label
            'saveAs' => __('Save as %s', 'publishpress-statuses'),
            'setSelected' => __('Set Selected Status', 'publishpress-statuses'),
            'keepCurrent'=> __('Keep Current Status', 'publishpress-statuses'),
            'advanceNext' => __('Advance to Next Status', 'publishpress-statuses'),
            'advanceMax' => __('Advance to Max Status', 'publishpress-statuses'),
            'currentlyPublished' => __('This post is currently published', 'publishpress-statuses'),
            'currentlyScheduled' => __('This post is currently scheduled', 'publishpress-statuses')
        ];

        if (!empty($post)) {
            wp_localize_script(
                'publishpress-custom-status-block',
                'PPCustomStatuses',
                apply_filters(
                    'pp_statuses_custom_status_block_args',
                    [
                        'statusRestProperty' => apply_filters('publishpress_statuses_rest_property', 'status', $post),
                        'statuses' => $statuses, 
                        'publishedStatuses' => $published_statuses, 
                        'publishedStatusObjects' => $published_status_objects, 
                        'captions' => apply_filters('publishpress_statuses_workflow_captions', $captions, $post),
                        'ajaxurl' => admin_url('admin-ajax.php'), 
                        'ppNonce' => wp_create_nonce('pp-custom-statuses-nonce')
                    ],
                    $post
                )
            );
        }
        
        add_action('admin_print_scripts', [$this, 'actPrintScripts']);

        global $wp_version;

        if (
        	(version_compare($wp_version, '6.6', '>=') && !defined('GUTENBERG_VERSION')) 
        	|| (defined('GUTENBERG_VERSION') && version_compare(GUTENBERG_VERSION, '18.5', '>='))
        ) {
            wp_enqueue_script(
                'publishpress-post-edit-sidebar',
                PUBLISHPRESS_STATUSES_URL . "common/js/post-block-edit-sidebar{$suffix}.js",
                ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-hooks'],
                PUBLISHPRESS_STATUSES_VERSION,
                true
            );
        }
    }

    function actPrintScripts() {
        global $wp_version;

        if (
        	(version_compare($wp_version, '6.6', '>=') && !defined('GUTENBERG_VERSION')) 
        	|| (defined('GUTENBERG_VERSION') && version_compare(GUTENBERG_VERSION, '18.5', '>='))
        ) :?>
            <style type="text/css">
            div.publishpress-extended-post-status div.components-flex-item label {
                text-transform: none !important;
                font-size: inherit !important;
            }

            div.publishpress-extended-post-status {
                margin-top: 0;
                margin-bottom: 8px;
            }
            </style>
        <?php endif;
    }

    /**
     * Get all post statuses as an ordered array
     *
     * @param array|string $statuses
     * @param array        $args
     * @param bool         $only_basic_info
     *
     * @return array $statuses All of the statuses
     */
    private function getStatuses($args = false, $only_basic_info = false)
    {
        global $post;
        $post_type = \PublishPress_Functions::findPostType();

        if (!is_array($args)) {
            $args = ['moderation' => true];
        }

        $args = array_merge($args, compact('post_type'));

        if (!empty($args['moderation'])) {
	        $draft_obj = get_post_status_object('draft');
	
	        $ordered_statuses = array_merge(
	            ['draft' => (object)['name' => 'draft', 'label' => esc_html(\PublishPress_Statuses::__wp('Draft')), 'icon' => $draft_obj->icon, 'color' => $draft_obj->color]],
	
	            array_diff_key(
	                    \PublishPress_Statuses::getPostStati($args, 'object'),
	                ['future' => true]
	            ),
	
	            ['publish' => (object)['name' => 'publish', 'label' => esc_html(\PublishPress_Statuses::__wp('Published'))]],
	            ['future' => (object)['name' => 'future', 'label' => esc_html(\PublishPress_Statuses::__wp('Scheduled'))]]
	        );
        } else {
            $ordered_statuses = \PublishPress_Statuses::getPostStati($args, 'object');
        }

        $can_set_status = \PublishPress_Statuses::getUserStatusPermissions('set_status', $post_type, $ordered_statuses);
        
        if (!empty($post)) {
            $can_set_status[$post->post_status] = true;
        }

        $ordered_statuses = array_intersect_key($ordered_statuses, array_filter($can_set_status));

        // compat with js usage of term properties
        foreach($ordered_statuses as $key => $status_obj) {
            if (!isset($status_obj->slug)) {
                $ordered_statuses[$key]->slug = $status_obj->name;
                $ordered_statuses[$key]->description = '';
            }

            if (!empty($status_obj->status_parent) && !empty($ordered_statuses[$status_obj->status_parent])) {
                $ordered_statuses[$key]->label = '— ' . $status_obj->label;

                if (!empty($status_obj->caption)) {
                    $ordered_statuses[$key]->caption = '— ' . $status_obj->caption;
                }
            }
        }

        if (!empty($ordered_statuses['pending'])) {
            $_ordered = [];

            foreach ($ordered_statuses as $key => $status_obj) {
                $_ordered []= $status_obj;

                if ('pending' == $status_obj->name) {
                    $status_obj = get_post_status_object('pending');
                    $status_label = (!empty($status_obj)) ? $status_obj->label : esc_html(\PublishPress_Statuses::__wp('Pending Review'));

                    $labels = (object) [
                        'save_as' => (!empty($status_obj) && !empty($status_obj->labels) && !empty($status_obj->labels->save_as)) 
                        ? $status_obj->labels->save_as 
                        : \PublishPress_Statuses::__wp('Save as Pending'),
                        
                        'publish' => (!empty($status_obj) && !empty($status_obj->labels) && !empty($status_obj->labels->publish)) 
                        ? $status_obj->labels->publish 
                        : \PublishPress_Statuses::__wp('Submit for Review'),
                    ];

                    // Alternate item to allow use of "Save as Pending" button
                    //
                    // This will allow different behavior from the Submit button, 
                    // which may default to next/highest available workflow status.

                    $_ordered[]= (object)[
                        'name' => '_pending',
                        'label' => $status_label,
                        'labels' => $labels,
                        'icon' => $status_obj->icon,
                        'color' => $status_obj->color
                    ];
                } 
            }

            $ordered_statuses = $_ordered;
        }

        $ordered_statuses = array_values($ordered_statuses);

        if (!$ordered_statuses) {
            return [];
        }

        // compat with js usage of term properties
        foreach($ordered_statuses as $key => $status_obj) {
            if (!isset($status_obj->slug)) {
                $ordered_statuses[$key]->slug = $status_obj->name;
                $ordered_statuses[$key]->description = '';
            }

            if ('draft' == $status_obj->name) {
                $ordered_statuses[$key]->save_as = \PublishPress_Statuses::__wp('Save Draft', 'publishpress-statuses');
                $ordered_statuses[$key]->submit = $ordered_statuses[$key]->save_as;
            } else {
            	$ordered_statuses[$key]->save_as = (!empty($status_obj->labels->save_as)) ? $status_obj->labels->save_as : \PublishPress_Statuses::__wp('Save');
            	$ordered_statuses[$key]->submit = (!empty($status_obj->labels->publish)) ? $status_obj->labels->publish : __('Advance Status', 'publishpress-statuses');
            }
        }

        foreach ($ordered_statuses as $k => $status_obj) {
            if ('future' == $status_obj->name) {
                unset($ordered_statuses[$k]);
            }
        }

        return $ordered_statuses;
    }
}
