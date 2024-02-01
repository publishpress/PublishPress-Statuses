<?php
class PP_Statuses_PlannerImport extends PublishPress_Statuses {
    
    public function applyRequestedImport($terms, $args = []) {
        if (!isset($args['force_planner_import'])) {
            if ($force_planner_import = get_option('pp_statuses_force_planner_import')) {
                delete_option('pp_statuses_force_planner_import');
                $args['force_planner_import'] = $force_planner_import;
            }
        }

        if (!empty($args['force_planner_import'])) {
            if (!isset($args['replace_props'])) {
                // On explicit re-import, replace existing stored color, icon and labels unless this constant is set
                $args['replace_props'] = !defined('PP_STATUSES_PLANNER_IMPORT_KEEP_PROPS') || !PP_STATUSES_PLANNER_IMPORT_KEEP_PROPS;
            }
        }

        if (get_option('pp_statuses_force_status_control_import')) {
            delete_option('pp_statuses_force_status_control_import');

            $args['force_status_control_import'] = true;
        }
    
        if (get_option('pp_statuses_skip_status_control_import')) {
            $args['skip_status_control_import'] = true;
            delete_option('pp_statuses_skip_status_control_import');
        }
    
        // On auto import, keep existing stored color, icon and labels unless this constant is set
        if (!isset($args['replace_props'])) {
            $args['replace_props'] = defined('PP_STATUSES_PLANNER_IMPORT_REPLACE_PROPS') && PP_STATUSES_PLANNER_IMPORT_REPLACE_PROPS;
        }

        return self::plannerImport($terms, $args);
    }


    public function importEncodedProperties($terms, $args = []) {
        return self::applyRequestedImport($terms, $args = []);
    }

    /*
    * Import status positions, color, icon and description encoded by Planner and merge into existing Planner statuses
    */
    public function plannerImport($terms, $args = []) {
        $defaults = [
            'archived_term_descriptions' => [],
            'queued_term_descriptions' => [],
            'force_planner_import' => false,
            'force_status_control_import' => false,
            'skip_status_control_import' => false,
            'replace_props' => false,
        ];
        
        if (!$terms) {
            if (!$terms = get_terms(self::TAXONOMY_PRE_PUBLISH, ['hide_empty' => false])) {
                return $terms;
            }
        }

        update_option('publishpress_statuses_planner_import', PUBLISHPRESS_STATUSES_VERSION);
        update_option('publishpress_statuses_planner_import_gmt', gmdate("Y-m-d H:i:s"));

        $import_specs = [
            'version' => PUBLISHPRESS_STATUSES_VERSION,
            'planner_version' => get_option('publishpress_version'),
            'status_control_version' => get_option('pps_version'),
            'args' => wp_json_encode($args),
            'date' => gmdate("Y-m-d H:i:s"),
        ];

        update_option('publishpress_statuses_planner_import_args', $import_specs);

        if (isset($args['archived_term_descriptions'])) {
            $archived_term_descriptions = $args['archived_term_descriptions'];
        } else {
            $archived_term_descriptions = (array) get_option('pp_statuses_archived_term_properties');
        }

        if (isset($args['queued_term_descriptions'])) {
            $queued_term_descriptions = $args['queued_term_descriptions'];
        } else {
            $queued_term_descriptions = (array) get_option('pp_statuses_queued_term_properties');
        }

        $moderation_statuses = self::getPostStati(['moderation' => true, 'internal' => false], 'object');

        $core_statuses = $this->get_default_statuses(self::TAXONOMY_CORE_STATUS);
        $pseudo_statuses = $this->get_default_statuses(self::TAXONOMY_PSEUDO_STATUS);
        $default_privacy_statuses = $this->get_default_statuses(self::TAXONOMY_PRIVACY);

        $all_statuses = array_merge($core_statuses, $pseudo_statuses, $moderation_statuses, $default_privacy_statuses);

        $disabled_statuses = (array) get_option('publishpress_disabled_statuses');

        if (!$statuses_stored_status_positions = get_option('publishpress_status_positions')) {
            $statuses_stored_status_positions = [];
        }

        // If Permissions Pro was active with the Status Control module, migrate its status order and parent values instead of Planner's position value
        // (but only if last Permissions Pro version was at least 3.11.0). In the Planner 3.x + Permissions Pro 3, these values overruled Planner's status positions
        //
        // We don't have a record of when Permissions / Status Control was deactivated, so this mitigates the risk of importing status order from an abandoned configuration.
        //
        // Do this only once, before status positions have been stored by the Statuses plugin (or after they have been reset).
        $do_status_control_import = false;

        $pps_version = (array) get_option('pps_version');
        $status_control_last_version = (isset($pps_version['version'])) ? $pps_version['version'] : '';

        if ((($status_control_last_version && version_compare($status_control_last_version, '3.11.0', '>=')) || !empty($args['force_status_control_import']))
        && (!$statuses_stored_status_positions || !empty($args['force_status_control_import']))
        && empty($args['skip_status_control_import']) && !defined('PP_STATUSES_NO_STATUS_CONTROL_IMPORT') && !defined('PUBLISHPRESS_STATUSES_NO_PERMISSIONS_IMPORT')
        ) {
            $do_status_control_import = true;

            update_option('publishpress_statuses_status_control_import', PUBLISHPRESS_STATUSES_VERSION);

            $presspermit_default_status_order = [
                'draft' => 0,
                'pitch' => 2,
                'assigned' => 5,
                'in-progress' => 7,
                'pending' => 10,
                'pending-review' => 10, // probably unused
                //'approved' => 18,     // this is applied as a default spacing relative to the order of the Pending status
            ];

            $presspermit_status_order = array_merge(
                $presspermit_default_status_order,
                (array) get_option('presspermit_status_order')
            );

            if (!isset($presspermit_status_order['approved'])) {
                $presspermit_status_order['approved'] = $presspermit_status_order['pending'] + 8;
            }

            // Import status parent
            $presspermit_status_parent = (array) get_option('presspermit_status_parent');

            foreach ($presspermit_status_parent as $status_name => $status_parent) {
                if (empty($all_statuses[$status_name])) {
                    continue;
                }

                // Used downstream in this function
                if (isset($moderation_statuses[$status_name])) {
                    $moderation_statuses[$status_name]->status_parent = $status_parent;
                }
            
                if ($status_parent) {
                    $term = get_term_by('slug', $status_name, self::TAXONOMY_PRE_PUBLISH);

                    if (!$term && !empty($all_statuses[$status_name])) {
                        \PublishPress_Statuses::instance()->addStatus(
                            self::TAXONOMY_PRE_PUBLISH, 
                            $all_statuses[$status_name]->label, 
                            ['slug' => $status_name]
                        );
                    }

                    if (!get_term_meta($term->term_id, 'status_parent', true) || !empty($args['replace_props'])) {
                        update_term_meta($term->term_id, 'status_parent', $status_parent);
                    }
                }
            }

            // Import post types
            $presspermit_status_post_types = (array) get_option('presspermit_status_post_types');

            foreach ($presspermit_status_post_types as $status_name => $post_types) {
                if (empty($all_statuses[$status_name])) {
                    continue;
                }

                if ($post_types) {
                    $term = get_term_by('slug', $status_name, self::TAXONOMY_PRE_PUBLISH);

                    if (!$term && !empty($all_statuses[$status_name])) {
                        \PublishPress_Statuses::instance()->addStatus(
                            self::TAXONOMY_PRE_PUBLISH, 
                            $all_statuses[$status_name]->label, 
                            ['slug' => $status_name]
                        );
                    }

                    if (!get_term_meta($term->term_id, 'post_type', true) || !empty($args['replace_props'])) {
                        update_term_meta($term->term_id, 'post_type', $post_types);
                    }
                }
            }

            // Import labels
            $presspermit_status_labels = (array) get_option('presspermit_custom_conditions_post_status');

            foreach ($presspermit_status_labels as $status_name => $labels) {
                if (empty($all_statuses[$status_name])) {
                    continue;
                }
                
                if ($labels) {
                    $term = get_term_by('slug', $status_name, self::TAXONOMY_PRE_PUBLISH);

                    if (!$term) {
                        if (!empty($all_statuses[$status_name])) {
                            \PublishPress_Statuses::instance()->addStatus(
                                self::TAXONOMY_PRE_PUBLISH, 
                                $all_statuses[$status_name]->label, 
                                ['slug' => $status_name]
                            );
                        } else {
                            continue;
                        }
                    }
                    
                    if (!empty($labels['label'])) {
                        wp_update_term(
                            $term->term_id, 
                            $term->taxonomy, 
                            ['name' => $labels['label']]
                        );
                    }

                    if (!get_term_meta($term->term_id, 'labels', true) || !empty($args['replace_props'])) {
                        $new_labels = (object) [];

                        if (!empty($labels['save_as_label'])) {
                            $new_labels->save_as = $labels['save_as_label'];
                        }

                        if (!empty($labels['publish_label'])) {
                            $new_labels->publish = $labels['publish_label'];
                        }

                        update_term_meta($term->term_id, 'labels', $new_labels);
                    }
                }
            }
        }

        // Setup for possible Planner positions import
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

        foreach (['draft', 'pending', 'future', 'publish', 'private'] as $status_name) {
            if ($val = get_option("psppno_status_{$status_name}_position")) {
                $planner_status_positions[$status_name] = $val;
            }
        }

        // This option array was created or updated immediately upstream prior to calling this function.
        $processed_term_descriptions = [];
        $cleared_term_descriptions = [];

        $any_new_planner_imports = false;

        $statuses_before_pending = [];
        $statuses_after_pending = [];
        $alternate_statuses = [];
        $statuses_after_publish = [];
        $disabled_statuses = [];

        $planner_unpositioned_statuses = [];

        // Scan terms that had encoded status properties (color, icon, position) retrieved from their description column.
        // Save each property to term_meta if another record doesn't already exist for it.
        foreach ($terms as $k => $term) {
            if (!$meta = get_term_meta($term->term_id)) {
                $meta = [];
            }

            if (is_array($archived_term_descriptions) && !empty($archived_term_descriptions[$term->term_id])) {
                $unencoded_description = maybe_unserialize(base64_decode($archived_term_descriptions[$term->term_id]));

                // User-created Planner terms without a position stored by Planner go to the bottom of the list
                if (!$do_status_control_import) {
                    if ($unencoded_description && !isset($unencoded_description['position'])
                    && (!empty($all_statuses[$term->slug]) && empty($all_statuses[$term->slug]->pp_builtin))
                    ) {
                        $planner_unpositioned_statuses[$term->slug] = $all_statuses[$term->slug]->label;
                    }
                }

                if (empty($queued_term_descriptions[$term->term_id]) && empty($args['force_planner_import'])) {
                    continue;
                }

                if (!is_array($unencoded_description)) {
                    $cleared_term_descriptions[$term->term_id] = true;
                    unset($archived_term_descriptions[$term->term_id]);
                    continue;
                }
            } else {
                // Precautionary fallback: If this term still has an encoded description, log it and clear the term field
                $unencoded_description = maybe_unserialize(base64_decode($term->description));

                if (is_array($unencoded_description)) {
                    wp_update_term($term->term_id, $term->taxonomy, ['description' => '']);
                    $terms[$k]->description = '';
                }
            }

            // Save the unencoded Planner-stored properties to term_meta (unless another record already exists)
            if (is_array($unencoded_description)) {
                foreach ($unencoded_description as $key => $value) {
                    if (in_array($key, ['position', 'color', 'icon'])) {
                        if ('position' == $key) {
                            $key = 'original_position';  // archive the original position value from prior to import

                            $planner_status_positions[$term->slug] = $value;

                            $any_new_planner_imports = true;
                        }

                        if (!isset($meta[$key]) || !empty($args['replace_props'])) {
                            update_term_meta($term->term_id, $key, $value);
                        }

                    } elseif (('description' == $key) && (!$term->description || ('-' == $term->description))) {
                        // Save the actual description string to term_meta
                        if ($value && ('-' != $value) && !preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $term->description)) {
                            wp_update_term(
                                $term->term_id, 
                                $term->taxonomy, 
                                ['description' => $value]
                            );
                        }
                    }
                }
            }

            unset($queued_term_descriptions[$term->term_id]);
            $processed_term_descriptions = true;
        }

        // Planner sorts unpositioned statuses alphabetically by label
        asort($planner_unpositioned_statuses);

        foreach (array_keys($planner_unpositioned_statuses) as $status_name) {
            // We want these after other Planner statuses in the Alternate section
            $planner_status_positions[$status_name] = max($planner_status_positions) + 1;
            $planner_unpositioned_statuses[$status_name] = $status_name;
        }

        if ($processed_term_descriptions) {
            update_option('pp_statuses_queued_term_properties', $queued_term_descriptions);
        }

        if ($cleared_term_descriptions) {
            update_option('pp_statuses_archived_term_properties', $archived_term_descriptions);
        }

        if (!$any_new_planner_imports && !$do_status_control_import) {
            delete_option('publishpress_statuses_planner_import_gmt');
            return $terms;
        }

        // Account for Planner-defined custom statuses which were deleted in Planner
        foreach (['pitch', 'assigned', 'in-progress'] as $status_name) {
            if (!in_array($status_name, $statuses_stored_status_positions) || !empty($args['force_planner_import'])) {
                if (!get_term_by('slug', $status_name, self::TAXONOMY_PRE_PUBLISH)) {
                    $disabled_statuses[$status_name]= $status_name;

                    unset($planner_status_positions[$status_name]);
                    $statuses_stored_status_positions = array_diff($statuses_stored_status_positions, [$status_name]);
                    unset($all_statuses[$status_name]);
                }
            }
        }

        asort($planner_status_positions);

        $term_meta_fields = apply_filters('publishpress_statuses_meta_fields', ['labels', 'post_type', 'roles', 'status_parent', 'color', 'icon']);

        $status_positions = (is_array($statuses_stored_status_positions) && $statuses_stored_status_positions) ? array_flip($statuses_stored_status_positions) : [];

        // Merge stored positions with defaults
        foreach ($all_statuses as $status_name => $status) {
            if (empty($status_positions[$status_name])) {
                $status_positions[$status_name] = (!empty($status->position)) ? $status->position : 0;
            }
        }

        // Map new PublishPress Statuses built-ins to relative PressPermit Status Control order
        // (This is only done if Statuses has not yet saved status positions or added any new statuses)
        if ($do_status_control_import) {
            $presspermit_alternate_position = 99;   // statuses in PressPermit effectively default to an order of 100
            $statuses_alternate_position = 200;     // ensure Statuses-defined alternates are listed below any Permissions-defined alternates we're importing
            $default_published_position = 1000;
            $default_disabled_position = 2000;

            $private_statuses = self::getPostStati(['private' => true], 'names');

            foreach (array_keys($status_positions) as $status_name) {
                if (in_array($status_name, ['draft', 'future', 'publish', 'private', '_pre-publish-alternate', '_disabled']) 
                || isset($pseudo_statuses[$status_name])
                ) {
                    // these positions are set directly as needed
                    continue;

                // If a status was disabled in Statuses config, keep that
                } elseif (isset($status_positions[$status_name]) && !empty($status_positions['_disabled']) 
                && ($status_positions[$status_name] > $status_positions['_disabled']) 
                ) {
                    $status_positions[$status_name] = $default_disabled_position + $status_positions[$status_name];

                } elseif (in_array($status_name, $private_statuses) || isset($default_privacy_statuses[$status_name])) {
                    $status_positions[$status_name] = $default_published_position + 2 + $status_positions[$status_name];

                } else {
                    if (isset($presspermit_status_order[$status_name])) {
                        // Use default / stored PressPermit status orders as is because we are mapping Statuses defaults around them.
                        $status_positions[$status_name] = $presspermit_status_order[$status_name]; 
                    } else {
                        $status_positions[$status_name] = $presspermit_alternate_position + $status_positions[$status_name];
                    }
                }
            }

            $status_positions['_pre-publish-alternate'] = $presspermit_alternate_position;  // intentional mapping of presspermit alternates ahead of Statuses alternates

            $status_positions['deferred'] = $statuses_alternate_position + 1;
            $status_positions['needs-work'] = $statuses_alternate_position + 2;
            $status_positions['rejected'] = $statuses_alternate_position + 3;

            $status_positions['future'] = $default_published_position - 1;
            $status_positions['publish'] = $default_published_position;
            $status_positions['private'] = $default_published_position + 1;

            $status_positions['_disabled'] = $default_disabled_position; 

            $status_positions['committee'] = $default_disabled_position + 1;
            $status_positions['committee-review'] = $default_disabled_position + 2;
            $status_positions['committee-progress'] = $default_disabled_position + 3;
            $status_positions['committee-approved'] = $default_disabled_position + 4;

            asort($status_positions);
        }

        // step through Planner-stored statuses
        // In the case of Status Control import, the order of Statuses defaults aligned with Status Control defaults above.
        // Then they are blended and stored using the second Statuses loop lower in this function below (NOT this one)
        if (! $do_status_control_import) {
            foreach ($planner_status_positions as $status_name => $position) {
                if (in_array($status_name, ['draft', 'pending', 'publish', 'private', 'future', '_pre-publish-alternate', '_disabled'])
                || isset($core_statuses[$status_name]) || isset($pseudo_statuses[$status_name])
                ) {
                    continue;
                }

                // On auto-import, use Planner-stored properties only for statuses we haven't already stored the order for
                if (in_array($status_name, $statuses_stored_status_positions) && empty($args['force_planner_import'])) {
                    continue;
                }

                if ($status_obj = get_post_status_object($status_name)) {
                    if (!empty($status_obj->private) || !empty($status_obj->public)) {
                        $statuses_after_publish[$status_name] = $status_name;
                        continue;
                    }
                }

                if (isset($statuses_before_pending[$status_name]) || isset($statuses_after_pending[$status_name]) || isset($alternate_statuses[$status_name])
                || isset($statuses_after_publish[$status_name]) || isset($disabled_statuses[$status_name])) {
                    continue;
                }

                if (isset($status_positions[$status_name]) && !empty($status_positions['_disabled']) 
                && ($status_positions[$status_name] > $status_positions['_disabled']) 
                && (!in_array($status_name, ['committee', 'committee-review', 'committee-progress', 'committee-approved']))  // If these are already defined by Planner, don't move to disabled
                ) {
                    $disabled_statuses[$status_name]= $status_name;

                    // retain any nesting established since Statuses install, unless forcing a Planner import
                    foreach (self::getStatusChildNames($status_name, $moderation_statuses) as $child_status) {
                        unset($statuses_before_pending[$child_status]);
                        unset($statuses_after_pending[$child_status]);
                        unset($alternate_statuses[$child_status]);
                        unset($disabled_statuses[$child_status]);

                        $disabled_statuses[$child_status] = $child_status;
                    }

                } elseif ($position <= $planner_status_positions['pending']) {
                    $statuses_before_pending[$status_name]= $status_name;

                    // retain any nesting established since Statuses install, unless forcing a Planner import
                    if (empty($args['force_planner_import']) || $do_status_control_import) {
                        foreach (self::getStatusChildNames($status_name, $moderation_statuses) as $child_status) {
                            if (!isset($disabled_statuses[$child_status])) {
                                unset($statuses_before_pending[$child_status]);
                                unset($statuses_after_pending[$child_status]);
                                unset($alternate_statuses[$child_status]);
                            }

                            $statuses_before_pending[$child_status] = $child_status;
                        }
                    }
                } elseif ($position < $planner_status_positions['publish']) {
                    $statuses_after_pending[$status_name]= $status_name;

                    // retain any nesting established since Statuses install, unless forcing a Planner import
                    if (empty($args['force_planner_import']) || $do_status_control_import) {
                        foreach (self::getStatusChildNames($status_name, $moderation_statuses) as $child_status) {
                            if (!isset($disabled_statuses[$child_status])) {
                                unset($statuses_before_pending[$child_status]);
                                unset($statuses_after_pending[$child_status]);
                                unset($alternate_statuses[$child_status]);

                                $statuses_after_pending[$child_status] = $child_status;
                            }
                        }
                    }
                } elseif (!isset($status_positions[$status_name]) || !empty($args['force_planner_import'])) { // if this status has been moved into workflow since Planner install, keep that position
                    $alternate_statuses[$status_name]= $status_name;

                    // retain any nesting established since Statuses install, unless forcing a Planner import
                    if (empty($args['force_planner_import']) || $do_status_control_import) {
                        foreach (self::getStatusChildNames($status_name, $moderation_statuses) as $child_status) {
                            if (!isset($disabled_statuses[$child_status])) {
                                unset($statuses_before_pending[$child_status]);
                                unset($statuses_after_pending[$child_status]);
                                unset($alternate_statuses[$child_status]);

                                $alternate_statuses[$child_status] = $child_status;
                            }
                        }
                    }
                }
            }

            // User-created Planner terms without a position stored by Planner go to the bottom of the list
            $alternate_statuses = array_merge($alternate_statuses, $planner_unpositioned_statuses);
        }
        
        // Step through Statuses-stored statuses to follow up any new Planner imports with previously saved Statuses positions
        // This loop is also used for PressPermit Status Control import
        foreach ($status_positions as $status_name => $position) {
            if (in_array($status_name, ['draft', 'pending', 'publish', 'private', 'future', '_pre-publish-alternate', '_disabled'])
            || isset($core_statuses[$status_name]) || isset($pseudo_statuses[$status_name])
            ) {
                continue;
            }

        	// Give precedence to Planner order if this is a forced re-import
            // (But still retain a disabled status configuration)
            if (!empty($args['force_planner_import']) && empty($do_status_control_import) && !empty($planner_status_positions[$status_name])) {
                if (!isset($status_positions[$status_name]) || empty($status_positions['_disabled']) 
                || ($status_positions[$status_name] <= $status_positions['_disabled']) 
                && (!empty($statuses_stored_status_positions) || !in_array($status_name, ['committee', 'committee-review', 'committee-progress', 'committee-approved']))
                ) {
                    continue;
                }
            }

            if ($status_obj = get_post_status_object($status_name)) {
                if (!empty($status_obj->private) || !empty($status_obj->public)) {
                    $statuses_after_publish[$status_name] = $status_name;
                    continue;
                }
            }

            if (isset($statuses_before_pending[$status_name]) || isset($statuses_after_pending[$status_name]) || isset($alternate_statuses[$status_name])
            || isset($statuses_after_publish[$status_name]) || isset($disabled_statuses[$status_name])) {
                continue;
            }

            if (isset($status_positions[$status_name]) && !empty($status_positions['_disabled']) 
            && ($status_positions[$status_name] > $status_positions['_disabled']) 
            ) {
                $disabled_statuses[$status_name]= $status_name;

                // retain any nesting established since Statuses install
                foreach (self::getStatusChildNames($status_name, $moderation_statuses) as $child_status) {
                    unset($statuses_before_pending[$child_status]);
                    unset($statuses_after_pending[$child_status]);
                    unset($alternate_statuses[$child_status]);
                    unset($disabled_statuses[$child_status]);

                    $disabled_statuses[$child_status] = $child_status;
                }

            } elseif ($position <= $status_positions['pending']) {
                $statuses_before_pending[$status_name]= $status_name;

                // retain any nesting established since Statuses install
                foreach (self::getStatusChildNames($status_name, $moderation_statuses) as $child_status) {
                    if (!isset($disabled_statuses[$child_status])) {
                        unset($statuses_before_pending[$child_status]);
                        unset($statuses_after_pending[$child_status]);
                        unset($alternate_statuses[$child_status]);
                    }

                    $statuses_before_pending[$child_status] = $child_status;
                }

            } elseif (((isset($status_positions['_pre-publish-alternate']) && $position < $status_positions['_pre-publish-alternate']))
            || (!isset($status_positions['_pre-publish-alternate']) && $position <= $planner_status_positions['publish'])
            ) {
                $statuses_after_pending[$status_name]= $status_name;

                // retain any nesting established since Statuses install
                foreach (self::getStatusChildNames($status_name, $moderation_statuses) as $child_status) {
                    if (!isset($disabled_statuses[$child_status])) {
                        unset($statuses_before_pending[$child_status]);
                        unset($statuses_after_pending[$child_status]);
                        unset($alternate_statuses[$child_status]);

                        $statuses_after_pending[$child_status] = $child_status;
                    }
                }
            } else {
                $alternate_statuses[$status_name]= $status_name;

                // retain any nesting established since Statuses install
                foreach (self::getStatusChildNames($status_name, $moderation_statuses) as $child_status) {
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
            $status_name = $term->slug;

            if ($status_obj = get_post_status_object($status_name)) {
                if (!empty($status_obj->private) || !empty($status_obj->public)) {
                    $statuses_after_publish[$status_name] = $status_name;
                    continue;
                }
            }

            if (isset($statuses_before_pending[$status_name]) || isset($statuses_after_pending[$status_name]) || isset($alternate_statuses[$status_name])
            || isset($statuses_after_publish[$status_name]) || isset($disabled_statuses[$status_name])) {
                continue;
            }

            $alternate_statuses[$status_name] = $status_name;
        }

        // build the status positions array
        $update_status_positions = ['draft'];

        foreach($statuses_before_pending as $status_name) {
            if (!in_array($status_name, $update_status_positions)) {
                $update_status_positions []= $status_name;
            }
        }

        $update_status_positions []= 'pending';

        foreach($statuses_after_pending as $status_name) {
            if (!in_array($status_name, $update_status_positions)) {
                $update_status_positions []= $status_name;
            }
        }

        $update_status_positions []= '_pre-publish-alternate';

        foreach($alternate_statuses as $status_name) {
            if (!in_array($status_name, $update_status_positions)) {
                $update_status_positions []= $status_name;
            }
        }

        $update_status_positions []= 'future';
        $update_status_positions []= 'publish';
        $update_status_positions []= 'private';

        foreach($statuses_after_publish as $status_name) {
            if (!in_array($status_name, $update_status_positions)) {
                $update_status_positions []= $status_name;
            }
        }

        $update_status_positions []= '_disabled';

        foreach($disabled_statuses as $status_name) {
            if (!in_array($status_name, $update_status_positions)) {
                $update_status_positions []= $status_name;
            }
        }

        update_option('publishpress_status_positions', $update_status_positions);

        if (!get_option('publishpress_archived_status_positions')) {
            update_option('publishpress_archived_status_positions', $status_positions);
        }

        // Apply page parent dis-associations caused by repositioning, matching the displayed nesting
        $parent_info = [];

        foreach ($terms as $k => $term) {
            if ($status_parent = get_term_meta($term->term_id, 'status_parent', true)) {
                $parent_info[$term->slug] = (object) [
                    'term_id' => $term->term_id,
                    'status_parent' => $status_parent
                ];
            }
        }
        
        $last_top_level = '';
        foreach ($update_status_positions as $status_name) {
            if (!empty($parent_info[$status_name])) {
                if ($parent_info[$status_name]->status_parent != $last_top_level) {
                    delete_term_meta($parent_info[$status_name]->term_id, 'status_parent');
                    $last_top_level = $status_name;
                }
            } else {
                $last_top_level = $status_name;
            }
        }

        $publishpress_version = get_option('publishpress_version');
        $pps_ver = get_option('pps_version');

        $import_specs = [
            'version' => PUBLISHPRESS_STATUSES_VERSION,
            'planner_version' => (!empty($publishpress_version)) ? $publishpress_version : '',
            'status_control_version' => (!empty($pps_ver)) ? $pps_ver : '',
            'args' => wp_json_encode($args),
            'vars' => compact('any_new_planner_imports',  'do_status_control_import', 'planner_status_positions'),
            'date' => gmdate("Y-m-d H:i:s"),
        ];

        if (!get_option('publishpress_statuses_planner_import_completed') && !get_option('publishpress_statuses_planner_original_import')) {
            update_option('publishpress_statuses_planner_original_import', $import_specs);
        }

        update_option('publishpress_statuses_planner_import_completed', $import_specs);

        delete_option('publishpress_statuses_planner_import_gmt');

        // Deactivation of auto-import is an automatic safety mechanism.
        // Successful completion of this manually invoked import means it's safe to support automatic follow-ups.
        if (!defined('PUBLISHPRESS_STATUSES_NO_AUTO_IMPORT')) {
            if (!$auto_import = \PublishPress_Statuses::instance()->options->auto_import) {
                $options = \PublishPress_Statuses::instance()->options;
                $options->auto_import = 1;
                update_option('publishpress_custom_status_options', $options);
            }
        }

        return $terms;
    }

    private static function applyPressPermitOrder($statuses, $args = [])
    {
        // convert integer keys to slugs
        foreach ($statuses as $status => $obj) {
            if (is_numeric($status)) {
                $statuses[$obj->name] = $obj;
                unset($statuses[$status]);
            }
        }

        $moderation_order = [];

        $main_order = [];
        foreach ($statuses as $status => $status_obj) {
            if (empty($status_obj->status_parent)) {
                $display_order = (!empty($status_obj->order)) ? $status_obj->order * 10000 : 1000000;

                while (isset($main_order[$display_order])) {
                    $display_order = $display_order + 100;
                }
                $main_order[$display_order] = $status;
            }
        }

        foreach ($statuses as $status => $status_obj) {
            $k = array_search($status, $main_order);
            if (false === $k) {
                $k = array_search($status_obj->status_parent, $main_order);
                if (false === $k) {
                    $k = 1000000;
                } else {
                    $order = (!empty($status_obj->order)) ? $status_obj->order : 100;
                    $k = $k + 1 + $order;
                }
            }

            $moderation_order[$k][$status] = $status_obj;
        }

        ksort($moderation_order);

        $statuses = [];
        foreach (array_keys($moderation_order) as $_order_key) {
            foreach ($moderation_order[$_order_key] as $status => $status_obj)
                $statuses[$status] = $status_obj;
        }

        return $statuses;
    }

}
