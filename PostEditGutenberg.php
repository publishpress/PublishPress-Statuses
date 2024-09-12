<?php
namespace PublishPress_Statuses;

class PostEditGutenberg
{
    /**
     * Enqueue Gutenberg assets.
     */
    public function actEnqueueBlockEditorAssets()
    {
        global $post;

        if (!empty($post)) {
            if (\PublishPress_Statuses::isUnknownStatus($post->post_status)
            || \PublishPress_Statuses::isPostBlacklisted($post->ID)
            ) {
                return;
            }
        }

        if ($post_id = \PublishPress_Functions::getPostID()) {
            if (defined('PUBLISHPRESS_REVISIONS_VERSION') && rvy_in_revision_workflow($post_id)) {
                return;
            }
        }

        $post_type = (!empty($post)) ? $post->post_type : \PublishPress_Statuses::getCurrentPostType();

        if (\PublishPress_Statuses::DisabledForPostType($post_type)) {
            return;
        }

        if (!$statuses = $this->getStatuses()) {
            return;
        }

        // Gutenberg Block Editor support for workflow status progression guidance / limitation
        require_once(__DIR__ . '/PostEditGutenbergStatuses.php');
        PostEditGutenbergStatuses::loadBlockEditorStatusGuidance();

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

        add_action('admin_print_scripts', [$this, 'actPrintScripts']);
        add_action('admin_print_footer_scripts', [$this, 'actPrintFooterScripts']);
    }

    function actPrintScripts() {
        global $wp_version;

        if (version_compare($wp_version, '6.6', '>=')) :?>
            <style type="text/css">
            div.publishpress-extended-post-status div.components-flex-item label {
                text-transform: none !important;
                font-size: inherit !important;
            }
            </style>
        <?php endif;
    }

    function actPrintFooterScripts() {
        global $wp_version;

        if (version_compare($wp_version, '6.6', '>=')) :?>
            <script type="text/javascript">
            /* <![CDATA[ */
            var ppRefreshA = false;
            var ppRefreshB = false;
            var ppRefreshC = false;
            var ppLastStatusWindowVisible = false;

            jQuery(document).ready(function ($) {
                setInterval(function() {
                    var statusCaption = '';

                    if ($('div.publishpress-extended-post-status select:visible').length && !$('div.editor-change-status__options:visible').length) {
                        statusCaption = $('div.publishpress-extended-post-status select option:selected').clone().html();
                    } else {
                        if (($('div.publishpress-extended-post-privacy select:visible').length || $('div.publishpress-extended-post-privacy div:visible').length) 
                        && !$('div.editor-change-status__options:visible').length)
                        {
                            statusCaption = $('div.publishpress-extended-post-privacy select option:selected').clone().html();
                        } else {
                            statusCaption = $('div.editor-change-status__options input:checked').siblings('label.components-radio-control__label').clone().html();

                            if ('undefined' !== typeof(statusCaption)) {
                                var iPos;
                                if (iPos = statusCaption.indexOf('<')) {
                                    statusCaption = statusCaption.substring(0, iPos);
                                }
                            }
                        }
                    }

                    if ('undefined' !== typeof(statusCaption)) {
                        if ($('div.editor-post-status button').html()) {
                            $('span.presspermit-status-span').remove();

                        } else {
                            if (!$('div.editor-post-status.is-read-only')) {
                                var hideClass = 'presspermit-save-hidden';
                                var node = $('div.editor-post-status');

                                if (!$('.presspermit-status-span').length) {
                                    node.after('<span class="presspermit-status-span">' + node.clone().css('z-index', 0).removeClass(hideClass).removeClass('editor-post-status').removeAttr('disabled').removeAttr('aria-disabled').css('white-space', 'nowrap').css('pointer-events', 'none').css('color', '#007cba').wrap('<span>').html() + '</span>');
                                    $('span.presspermit-status-span').css('pointer-events', 'none');
                                }

                                $('.editor-post-status button, .presspermit-status-span button').css('width', 40 + (6 * statusCaption.length));

                                var leftPos = $('.editor-post-status').offset().left - $('.presspermit-status-span').offset().left;

                                $('.presspermit-status-span button').css('position', 'relative').css('left', leftPos).css('top', 0);

                                $('.presspermit-status-span button').html(statusCaption).show();
                            }
                        }
                    }

                    if (!ppRefreshA && $('div.publishpress-extended-subpost-privacy:visible').length) {
                        ppRefreshA = true;
                        $('div.publishpress-extended-subpost-privacy').insertAfter($('div.editor-post-status').closest('div.editor-post-panel__row'));

                        setInterval(() => {
                            if (!$('div.publishpress-extended-subpost-privacy:visible').length) {
                                ppRefreshA = false;
                            }
                        }, 500);
                    }

                    if (!ppRefreshB && $('div.publishpress-extended-post-privacy:visible').length) {
                        ppRefreshB = true;
                        $('div.publishpress-extended-post-privacy').insertAfter($('div.editor-post-status').closest('div.editor-post-panel__row'));

                        setInterval(() => {
                            if (!$('div.publishpress-extended-post-privacy:visible').length) {
                                ppRefreshB = false;
                            }
                        }, 500);
                    }

                    if (!ppRefreshC && $('div.publishpress-extended-post-status:visible').length) {
                        ppRefreshC = true;
                        $('div.publishpress-extended-post-status').insertAfter($('div.editor-post-status').closest('div.editor-post-panel__row'));

                        setInterval(() => {
                            if (!$('div.publishpress-extended-post-status:visible').length) {
                                ppRefreshC = false;
                            }
                        }, 500);
                    }
                }, 200);

                setInterval(function() {
                    var statusWindowVisible = $('.editor-change-status__content:visible').length;

                    if (statusWindowVisible) {
                        if ($('div.publishpress-extended-post-status select:visible').length) {
                            $('.editor-change-status__options input[value="draft"],.editor-change-status__options input[value="pending"]').prop('disabled', 'disabled').parent().hide();
                        } else {
                            $('.editor-change-status__options input[value="draft"],.editor-change-status__options input[value="pending"]').removeProp('disabled').parent().show();
                        }
                    } else {
                        if (ppLastStatusWindowVisible) {
                            ppRefreshA = false;
                            ppRefreshB = false;
                            ppRefreshC = false;

                            setTimeout(function() {
                                if (!$('.publishpress-extended-post-status select:visible').length) {
                                    $('span.presspermit-editor-toggle').remove();
                                }

                                //if ('undefined' == typeof(window.PPCustomPrivacy)) {
                                    if (!$('.editor-post-save-draft').length) {
                                        if ($('.presspermit-editor-button button:visible').length) {
                                            $('.presspermit-editor-button button').show().css('z-index', 0);
                                        } else {
                                            $('.editor-post-publish-button, .editor-post-publish-button__button').show();
                                            $('.editor-post-publish-button').css('z-index', 0);
                                        }
                                    }
                                //}
                            }, 100);
                        }
                    }

                    ppLastStatusWindowVisible = statusWindowVisible;
                }, 500);
            });
            /* ]]> */
            </script>
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
    private function getStatuses($args = [], $only_basic_info = false)
    {
        global $post;
        $post_type = \PublishPress_Functions::findPostType();

        $draft_obj = get_post_status_object('draft');

        $ordered_statuses = array_merge(
            ['draft' => (object)['name' => 'draft', 'label' => esc_html(\PublishPress_Statuses::__wp('Draft')), 'icon' => $draft_obj->icon, 'color' => $draft_obj->color]],

            array_diff_key(
                \PublishPress_Statuses::getPostStati(['moderation' => true, 'post_type' => $post_type], 'object'),
                ['future' => true]
            ),

            ['publish' => (object)['name' => 'publish', 'label' => esc_html(\PublishPress_Statuses::__wp('Published'))]],
            ['future' => (object)['name' => 'future', 'label' => esc_html(\PublishPress_Statuses::__wp('Scheduled'))]]
        );

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
