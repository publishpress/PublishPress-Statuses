<?php
class PP_Statuses_PlannerImport extends PublishPress_Statuses {
    /*
    * Import status positions, color, icon and description encoded by Planner and merge into existing Planner statuses
    */

    public function importEncodedProperties($terms, $args = []) {
        update_option('publishpress_statuses_planner_import', PUBLISHPRESS_STATUSES_VERSION);
        
        if (!$terms) {
            if (!$terms = get_terms('post_status', ['hide_empty' => false])) {
                return;
            }
        }

        $planner_status_positions = [
            'pitch' => 1,
            'assigned' => 2,
            'in-progress' => 3,
            'draft' => 4,
            'pending' => 5,
            'future' => 7,
            'private' => 8,
            'publish' => 9,
        ];

        foreach (['draft', 'pending', 'future', 'publish', 'private'] as $status) {
            if ($val = get_option("psppno_status_{$status}_position")) {
                $planner_status_positions[$status] = $val;
            }
        }

        $any_planner_archives = false;

        foreach ($terms as $term) {
            if (!$meta = get_term_meta($term->term_id)) {
                $meta = [];
            }

            $archived_term_descriptions = maybe_unserialize(get_option('pp_statuses_archived_term_properties'));

            if (is_array($archived_term_descriptions) && !empty($archived_term_descriptions[$term->term_id])) {
                $unencoded_description = maybe_unserialize(base64_decode($archived_term_descriptions[$term->term_id]));  
            } else {
                $unencoded_description = maybe_unserialize(base64_decode($term->description));
            }

            if (!is_array($unencoded_description)) {
                continue;
            }

            foreach ($unencoded_description as $key => $value) {
                if (in_array($key, ['position', 'color', 'icon'])) {
                    if ('position' == $key) {
                        $key = 'original_position';

                        $planner_status_positions[$term->slug] = $value;
                    }

                    if (!isset($meta[$key]) || !empty($args['pp_overwrite_status_meta'])) {
                        if ('position' != $key) {
                            update_term_meta($term->term_id, $key, $value);
                        }
                    }

                    $any_planner_archives = true;

                } elseif (('description' == $key) && (!$term->description || ('-' == $term->description))) {
                    if ($value && ('-' != $value)) {
                        wp_update_term($term->term_id, 'post_status', ['description' => $value]);
                    }
                }
            }
        }

        if (!$any_planner_archives) {
            return;
        }

        asort($planner_status_positions);

        $core_statuses = $this->get_default_statuses(self::TAXONOMY_CORE_STATUS);
        $pseudo_statuses = $this->get_default_statuses(self::TAXONOMY_PSEUDO_STATUS);
        $default_moderation_statuses = $this->get_default_statuses(self::TAXONOMY_PRE_PUBLISH);
        $default_privacy_statuses = $this->get_default_statuses(self::TAXONOMY_PRIVACY);

        $all_statuses = array_merge($core_statuses, $pseudo_statuses, $default_moderation_statuses, $default_privacy_statuses);

        $disabled_statuses = (array) get_option('publishpress_disabled_statuses');

        $positions = get_option('publishpress_status_positions');

        $stored_status_positions = (is_array($positions) && $positions) ? array_flip($positions) : [];

        $stored_status_terms = [];

        $term_meta_fields = apply_filters('publishpress_statuses_meta_fields', ['labels', 'post_type', 'roles', 'status_parent', 'color', 'icon']);

        // Merge stored positions with defaults
        foreach ($all_statuses as $status_name => $status) {
            if (empty($stored_status_positions[$status_name])) {
                $stored_status_positions[$status_name] = (!empty($status->position)) ? $status->position : 0;
            }
        }

        $statuses_before_pending = [];
        $statuses_after_pending = [];
        $alternate_statuses = [];
        $statuses_after_publish = [];
        $disabled_statuses = [];

        $moderation_statuses = self::getPostStati(['moderation' => true, 'internal' => false], 'names');

        // step through Planner-stored statuses
        foreach ($planner_status_positions as $post_status => $position) {
            if (in_array($post_status, ['draft', 'pending', 'publish', 'private', 'future', '_pre-publish-alternate', '_disabled'])) {
                continue;
            }

            if ($status_obj = get_post_status_object($post_status)) {
                if (!empty($status_obj->private) || !empty($status_obj->public)) {
                    $statuses_after_publish[$post_status] = $post_status;
                    continue;
                }
            }

            if (isset($statuses_before_pending[$post_status]) || isset($statuses_after_pending[$post_status]) || isset($alternate_statuses[$post_status])
            || isset($statuses_after_publish[$post_status]) || isset($disabled_statuses[$post_status])) {
                continue;
            }

            // Default custom statuses which were deleted within Planner
            if (!get_term_by('slug', $post_status, 'post_status')) {
                $disabled_statuses[$post_status]= $post_status;
            
            } elseif (isset($stored_status_positions[$post_status]) && !empty($stored_status_positions['_disabled']) 
            && ($stored_status_positions[$post_status] > $stored_status_positions['_disabled']) 
            && (!in_array($post_status, ['committee', 'committee-review', 'committee-progress', 'committee-approved']))  // If these are already defined by Planner, don't move to disabled
            ) {
                $disabled_statuses[$post_status]= $post_status;

                // retain any nesting established since Statuses install
                foreach (self::getStatusChildren($post_status, $moderation_statuses) as $child_status) {
                    unset($statuses_before_pending[$child_status]);
                    unset($statuses_after_pending[$child_status]);
                    unset($alternate_statuses[$child_status]);
                    unset($disabled_statuses[$child_status]);

                    $disabled_statuses[$child_status] = $child_status;
                }

            } elseif ($position <= $planner_status_positions['pending'] 
            && (!isset($stored_status_positions['_pre-publish-alternate']) || !isset($stored_status_positions[$post_status])
            || $stored_status_positions[$post_status] < $stored_status_positions['_pre-publish-alternate'])
            ) {
                $statuses_before_pending[$post_status]= $post_status;

                // retain any nesting established since Statuses install
                foreach (self::getStatusChildren($post_status, $moderation_statuses) as $child_status) {
                    if (!isset($disabled_statuses[$child_status])) {
                        unset($statuses_before_pending[$child_status]);
                        unset($statuses_after_pending[$child_status]);
                        unset($alternate_statuses[$child_status]);
                    }

                    $statuses_before_pending[$child_status] = $child_status;
                }

            } elseif ($position < $planner_status_positions['publish']
            && (!isset($stored_status_positions['_pre-publish-alternate']) || !isset($stored_status_positions[$post_status])
            || $stored_status_positions[$post_status] < $stored_status_positions['_pre-publish-alternate'])
            ) {
                $statuses_after_pending[$post_status]= $post_status;

                // retain any nesting established since Statuses install
                foreach (self::getStatusChildren($post_status, $moderation_statuses) as $child_status) {
                    if (!isset($disabled_statuses[$child_status])) {
                        unset($statuses_before_pending[$child_status]);
                        unset($statuses_after_pending[$child_status]);
                        unset($alternate_statuses[$child_status]);

                        $statuses_after_pending[$child_status] = $child_status;
                    }
                }
            } elseif (!isset($stored_status_positions[$post_status])) { // if this status has been moved into workflow since Planner install, keep that position
                $alternate_statuses[$post_status]= $post_status;

                // retain any nesting established since Statuses install
                foreach (self::getStatusChildren($post_status, $moderation_statuses) as $child_status) {
                    if (!isset($disabled_statuses[$child_status])) {
                        unset($statuses_before_pending[$child_status]);
                        unset($statuses_after_pending[$child_status]);
                        unset($alternate_statuses[$child_status]);

                        $alternate_statuses[$child_status] = $child_status;
                    }
                }
            }
        }

        // step through Statuses-stored statuses to include any created since install
        foreach ($stored_status_positions as $post_status => $position) {
            if (in_array($post_status, ['draft', 'pending', 'publish', 'private', 'future', '_pre-publish-alternate', '_disabled'])) {
                continue;
            }

            if (!in_array($post_status, ['deferred', 'rejected', 'committee', 'committee-review', 'committee-progress', 'committee-approved']) // If these are already defined by Planner, don't move or disable
            || !isset($planner_status_positions[$post_status])
            ) {
                continue;
            }

            if ($status_obj = get_post_status_object($post_status)) {
                if (!empty($status_obj->private) || !empty($status_obj->public)) {
                    $statuses_after_publish[$post_status] = $post_status;
                    continue;
                }
            }

            if (isset($statuses_before_pending[$post_status]) || isset($statuses_after_pending[$post_status]) || isset($alternate_statuses[$post_status])
            || isset($statuses_after_publish[$post_status]) || isset($disabled_statuses[$post_status])) {
                continue;
            }

            if (isset($stored_status_positions[$post_status]) && !empty($stored_status_positions['_disabled']) 
            && ($stored_status_positions[$post_status] > $stored_status_positions['_disabled']) 
            ) {
                $disabled_statuses[$post_status]= $post_status;

                // retain any nesting established since Statuses install
                foreach (self::getStatusChildren($post_status, $moderation_statuses) as $child_status) {
                    unset($statuses_before_pending[$child_status]);
                    unset($statuses_after_pending[$child_status]);
                    unset($alternate_statuses[$child_status]);
                    unset($disabled_statuses[$child_status]);

                    $disabled_statuses[$child_status] = $child_status;
                }

            } elseif ($position <= $stored_status_positions['pending']) {
                $statuses_before_pending[$post_status]= $post_status;

                // retain any nesting established since Statuses install
                foreach (self::getStatusChildren($post_status, $moderation_statuses) as $child_status) {
                    if (!isset($disabled_statuses[$child_status])) {
                        unset($statuses_before_pending[$child_status]);
                        unset($statuses_after_pending[$child_status]);
                        unset($alternate_statuses[$child_status]);
                    }

                    $statuses_before_pending[$child_status] = $child_status;
                }

            } elseif (((isset($stored_status_positions['_pre-publish-alternate']) && $position < $stored_status_positions['_pre-publish-alternate']))
            || (!isset($stored_status_positions['_pre-publish-alternate']) && $position <= $planner_status_positions['publish'])
            ) {
                $statuses_after_pending[$post_status]= $post_status;

                // retain any nesting established since Statuses install
                foreach (self::getStatusChildren($post_status, $moderation_statuses) as $child_status) {
                    if (!isset($disabled_statuses[$child_status])) {
                        unset($statuses_before_pending[$child_status]);
                        unset($statuses_after_pending[$child_status]);
                        unset($alternate_statuses[$child_status]);

                        $statuses_after_pending[$child_status] = $child_status;
                    }
                }
            } else {
                $alternate_statuses[$post_status]= $post_status;

                // retain any nesting established since Statuses install
                foreach (self::getStatusChildren($post_status, $moderation_statuses) as $child_status) {
                    if (!isset($disabled_statuses[$child_status])) {
                        unset($statuses_before_pending[$child_status]);
                        unset($statuses_after_pending[$child_status]);
                        unset($alternate_statuses[$child_status]);

                        $alternate_statuses[$child_status] = $child_status;
                    }
                }
            }
        }

        // step through Planner terms with no position stored
        foreach ($terms as $term) {
            $post_status = $term->slug;

            if ($status_obj = get_post_status_object($post_status)) {
                if (!empty($status_obj->private) || !empty($status_obj->public)) {
                    $statuses_after_publish[$post_status] = $post_status;
                    continue;
                }
            }

            if (isset($statuses_before_pending[$post_status]) || isset($statuses_after_pending[$post_status]) || isset($alternate_statuses[$post_status])
            || isset($statuses_after_publish[$post_status]) || isset($disabled_statuses[$post_status])) {
                continue;
            }

            $alternate_statuses[$post_status] = $post_status;
        }

        // build the status positions array
        $update_status_positions = ['draft'];

        foreach($statuses_before_pending as $post_status) {
            if (!in_array($post_status, $update_status_positions)) {
                $update_status_positions []= $post_status;
            }
        }

        $update_status_positions []= 'pending';

        foreach($statuses_after_pending as $post_status) {
            if (!in_array($post_status, $update_status_positions)) {
                $update_status_positions []= $post_status;
            }
        }

        $update_status_positions []= '_pre-publish-alternate';

        foreach($alternate_statuses as $post_status) {
            if (!in_array($post_status, $update_status_positions)) {
                $update_status_positions []= $post_status;
            }
        }

        $update_status_positions []= 'future';
        $update_status_positions []= 'publish';
        $update_status_positions []= 'private';

        foreach($statuses_after_publish as $post_status) {
            if (!in_array($post_status, $update_status_positions)) {
                $update_status_positions []= $post_status;
            }
        }

        $update_status_positions []= '_disabled';

        foreach($disabled_statuses as $post_status) {
            if (!in_array($post_status, $update_status_positions)) {
                $update_status_positions []= $post_status;
            }
        }

        update_option('publishpress_status_positions', $update_status_positions);

        if (!get_option('publishpress_archived_status_positions')) {
            update_option('publishpress_archived_status_positions', $stored_status_positions);
        } 

        update_option('publishpress_statuses_planner_import_completed', PUBLISHPRESS_STATUSES_VERSION);


        return true;
    }
}
