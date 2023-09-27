<?php
namespace PublishPress_Statuses;

class PostEditGutenberg
{
    function __construct() 
    {
        if ($post_id = \PublishPress_Functions::getPostID()) {
            if (defined('PUBLISHPRESS_REVISIONS_VERSION') && rvy_in_revision_workflow($post_id)) {
                return;
            }
        }
        
        add_action('enqueue_block_editor_assets', [$this, 'actEnqueueBlockEditorAssets']);

        // Gutenberg Block Editor support for workflow status progression guidance / limitation
        add_action('enqueue_block_editor_assets', [$this, 'act_status_guidance_scripts']);

        add_action('admin_enqueue_scripts', [$this, 'act_replace_publishpress_scripts'], 50);
    }

    // If PressPermit permissions filtering is enabled for this post type, replace certain PublishPress scripts with a permissions-aware equivalent
    public function act_replace_publishpress_scripts()
    {
        //if (\PublishPress_Statuses\PostEdit::isPostTypeEnabled()) {  @todo: check post type enable (for whole custom statuses feature)
            wp_enqueue_style(
                'publishpress-custom-status-block',
                PUBLISHPRESS_STATUSES_URL . 'common/custom-status-block-editor.css', 
                false,
                PUBLISHPRESS_STATUSES_VERSION,
                'all'
            );
        //}
    }

    // If PressPermit permissions filtering is enabled for this post type and the user may be limited, load scripts to support status progression guidance
    public function act_status_guidance_scripts()
    {
        require_once(__DIR__ . '/PostEditGutenbergStatuses.php');
        PostEditGutenbergStatuses::loadBlockEditorStatusGuidance();
    }

    /**
     * Enqueue Gutenberg assets.
     */
    public function actEnqueueBlockEditorAssets()
    {
        if (!$statuses = $this->getStatuses()) {
            return;
        }

        if (\PublishPress_Statuses::DisabledForPostType()) {
            return;
        }

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

        wp_enqueue_script(
            'publishpress-custom-status-block',
            PUBLISHPRESS_STATUSES_URL . "common/js/custom-status-block{$suffix}.js",
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
            'publish' => __('Publish', 'publishpress-statuses'),
            'schedule' => __('Schedule', 'publishpress-statuses'),
            'advance' => __('Advance Status', 'publishpress-statuses'),
            'saveAs' => __('Save as %s', 'publishpress-statuses'),
            'setSelected' => __('Set Selected Status', 'publishpress-statuses'),
            'keepCurrent'=> __('Keep Current Status', 'publishpress-statuses'),
            'advanceNext' => __('Advance to Next Status', 'publishpress-statuses'),
            'advanceMax' => __('Advance to Max Status', 'publishpress-statuses'),
            'currentlyPublished' => __('This post is currently published', 'publishpress-statuses'),
            'currentlyScheduled' => __('This post is currently scheduled', 'publishpress-statuses')
        ];

        wp_localize_script(
            'publishpress-custom-status-block',
            'PPCustomStatuses',
            [
                'statuses' => $statuses, 
                'publishedStatuses' => $published_statuses, 
                'publishedStatusObjects' => $published_status_objects, 
                'captions' => $captions,
                'ajaxurl' => admin_url('admin-ajax.php'), 
                'ppNonce' => wp_create_nonce('pp-custom-statuses-nonce')
            ]
        );
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
    private function getStatuses($args = [], $only_basic_info = false)
    {
        global $post;
        $post_type = \PublishPress_Functions::findPostType();

        $ordered_statuses = array_merge(
            ['draft' => (object)['name' => 'draft', 'label' => esc_html__('Draft')]],

            array_diff_key(
                \PublishPress_Statuses::getPostStati(['moderation' => true, 'post_type' => $post_type], 'object'),
                ['future' => true]
            ),

            ['publish' => (object)['name' => 'publish', 'label' => esc_html__('Published')]],
            ['future' => (object)['name' => 'future', 'label' => esc_html__('Scheduled')]]
        );

        // compat with js usage of term properties
        foreach($ordered_statuses as $key => $status_obj) {
            if (!isset($status_obj->slug)) {
                $ordered_statuses[$key]->slug = $status_obj->name;
                $ordered_statuses[$key]->description = '-';
                $ordered_statuses[$key]->color = '';
                $ordered_statuses[$key]->icon = '';
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
                    // Alternate item to allow use of "Save as Pending" button
                    //
                    // This will allow different behavior from the Submit button, 
                    // which may default to next/highest available workflow status.
                    $_ordered[]= (object)['name' => '_pending', 'label' => esc_html__('Pending')];
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
                $ordered_statuses[$key]->description = '-';
                $ordered_statuses[$key]->color = '';
                $ordered_statuses[$key]->icon = '';
            }

            $ordered_statuses[$key]->save_as = (!empty($status_obj->labels->save_as)) ? $status_obj->labels->save_as : __('Save', 'publishpress-statuses');
            $ordered_statuses[$key]->submit = (!empty($status_obj->labels->publish)) ? $status_obj->labels->publish : __('Advance Status', 'publishpress-statuses');
        }

        foreach ($ordered_statuses as $k => $status_obj) {
            if ('future' == $status_obj->name) {
                unset($ordered_statuses[$k]);
            }
        }

        return $ordered_statuses;
    }
}
