<?php
namespace PublishPress_Statuses;

// Custom Status management: Handle status add / edit requests
class StatusHandler {
    /**
     * Handles a form's POST request to add a custom status
     *
     */
    public static function handleAddCustomStatus()
    {
        global $current_user;

        check_admin_referer('custom-status-add-nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Sorry, you do not have permission to edit custom statuses.', 'publishpress-statuses'));
        }

        // Validate and sanitize the form data
        $status_label = sanitize_text_field(trim($_POST['status_label']));
        $status_name = sanitize_title($status_label);
        $status_description = stripslashes(wp_filter_nohtml_kses(trim($_POST['description'])));
        $status_color = sanitize_hex_color($_POST['status_color']);
        $status_icon = str_replace('dashicons|', '', $_POST['icon']);

        $taxonomy = (!empty($_POST['taxonomy'])) ? sanitize_key($_POST['taxonomy']) : \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH;

        if ($taxonomy && !in_array($taxonomy, [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_PRIVACY])) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH;
        }

        /**
         * Form validation
         * - Name is required and can't conflict with an existing name or slug
         * - Description is optional
         */
        $_REQUEST['form-errors'] = [];
        // Check if name field was filled in
        if (empty($status_label)) {
            $_REQUEST['form-errors']['label'] = __('Please enter a name for the status', 'publishpress-statuses');
        }
        // Check that the name isn't numeric
        if (is_numeric($status_label)) {
            $_REQUEST['form-errors']['label'] = __(
                'Please enter a valid, non-numeric name for the status.',
                'publishpress-statuses'
            );
        }
        // Check that the status name doesn't exceed 20 chars
        $name_is_valid = true;
        if (function_exists('mb_strlen')) {
            if (mb_strlen($status_label) > 20) {
                $name_is_valid = false;
            }
        } else {
            if (strlen($status_label) > 20) {
                $name_is_valid = false;
            }
        }
        if (! $name_is_valid) {
            $_REQUEST['form-errors']['label'] = __(
                'Status name cannot exceed 20 characters. Please try a shorter name.',
                'publishpress-statuses'
            );

            return;
        }

        // Check to make sure the status doesn't already exist as another term because otherwise we'd get a weird slug
        if (term_exists($status_name, 'post_status')) {
            $_REQUEST['form-errors']['label'] = __(
                'Status name conflicts with existing term. Please choose another.',
                'publishpress-statuses'
            );
        }
        // Check to make sure the name is not restricted
        if (self::is_restricted_status(strtolower($status_name))) {
            $_REQUEST['form-errors']['label'] = __(
                'Status name is restricted. Please choose another name.',
                'publishpress-statuses'
            );
        }

        // If there were any form errors, kick out and return them
        if (count($_REQUEST['form-errors'])) {
            $_REQUEST['error'] = 'form-error';

            return;
        }

        // Try to add the status
        $status_args = [
            'description' => $status_description,
            'name' => $status_name,
            'color' => $status_color,
            'icon' => $status_icon,
        ];
        
        $return = \PublishPress_Statuses::instance()->addStatus($taxonomy, $status_label, $status_args);

        if (is_wp_error($return)) {
            wp_die(__('Could not add status: ', 'publishpress-statuses') . $return->get_error_message());
        }

        $roles = ['administrator', 'editor', 'author', 'contributor'];
        foreach ($roles as $roleSlug) {
            $role = get_role($roleSlug);
            if (! empty($role)) {
                $role->add_cap('status_change_' . str_replace('-', '_', $status_name));
            }
        }

        delete_option('publishpress_statuses_num_roles');

        delete_user_meta($current_user->ID, 'publishpress_statuses_collapsed_sections');

        $redirect_args = ['message' => 'status-added'];

        // Redirect if successful
        $redirect_url = \PublishPress_Statuses::getLink($redirect_args);

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Handles a GET request to delete a specific term
     *
     */
    public static function handleDeleteCustomStatus()
    {
        // Check for proper nonce
        check_admin_referer('delete-status');

        // Only allow users with the proper caps
        if (! current_user_can('manage_options')) {
            wp_die(__('Sorry, you do not have permission to edit custom statuses.', 'publishpress-statuses'));
        }

        // Check to make sure the status isn't already deleted
        $name = sanitize_key($_GET['name']);
        $term = \PublishPress_Statuses::getStatusBy('id', $name);
        if (! $term) {
            wp_die(__('Status does not exist.', 'publishpress-statuses'));
        }

        $return = self::deleteCustomStatus($name);
        if (is_wp_error($return)) {
            wp_die(__('Could not delete the status: ', 'publishpress-statuses') . $return->get_error_message());
        }

        $redirect_url = \PublishPress_Statuses::getLink(['message' => 'status-deleted']);
        wp_redirect($redirect_url);

        exit;
    }

    
    /**
     * Handles a POST request to edit a custom status
     *
     */
    public static function handleEditCustomStatus()
    {
        check_admin_referer('edit-status');

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not permitted to do that.', 'publishpress-statuses'));
        }

        if (!$existing_status = \PublishPress_Statuses::getStatusBy('name', sanitize_key($_GET['name']))) {
            wp_die(__("Post status doesn't exist.", 'publishpress-statuses'));
        }

        $color = sanitize_hex_color($_POST['status_color']);
        $icon = sanitize_text_field($_POST['icon']);
        $icon = str_replace('dashicons|', '', $icon);

        $name = sanitize_key($_GET['name']);
        $status_obj = $existing_status;

        $name = sanitize_text_field(trim($_POST['name']));

        if (isset($_REQUEST['status_label'])) {
            $label = sanitize_text_field(trim($_POST['status_label']));
        }

        if (isset($_REQUEST['description'])) {
            $description = stripslashes(wp_filter_nohtml_kses(trim($_POST['description'])));
        }

        /**
         * Form validation for editing custom status
         *
         * Details
         * - 'name' is a required field and can't conflict with existing name or slug
         * - 'description' is optional
         */
        $_REQUEST['form-errors'] = [];

        if (isset($_REQUEST['status_label'])) {
            // Check if name field was filled in
            if (empty($label)) {
                $_REQUEST['form-errors']['status_label'] = __('Please enter a name for the status', 'publishpress-statuses');
            }

            // Check that the name isn't numeric
            if (is_numeric($label)) {
                $_REQUEST['form-errors']['status_label'] = __(
                    'Please enter a valid, non-numeric name for the status.',
                    'publishpress-statuses'
                );
            }
            // Check that the status name doesn't exceed 20 chars
            $name_is_valid = true;

            if (function_exists('mb_strlen')) {
                if (mb_strlen($label) > 20) {
                    $name_is_valid = false;
                }
            } else {
                if (strlen($label) > 20) {
                    $name_is_valid = false;
                }
            }

            if (! $name_is_valid) {
                $_REQUEST['form-errors']['status_label'] = __(
                    'Status name cannot exceed 20 characters. Please try a shorter name.',
                    'publishpress-statuses'
                );
            }
        }

        /*
        // Check to make sure the status doesn't already exist as another term because otherwise we'd get a weird slug
        $term_exists = term_exists(sanitize_title($name), 'post_status');

        if (is_array($term_exists)) {
            $term_exists = (int)$term_exists['slug'];
        }

        if ($term_exists && $term_exists != $existing_status->name) {
            $_REQUEST['form-errors']['status_label'] = __(
                'Status name conflicts with existing term. Please choose another.',
                'publishpress-statuses'
            );
        }
        // Check to make sure the status doesn't already exist
        $search_status = \PublishPress_Statuses::getStatusBy('slug', sanitize_title($label));

        if ($search_status && $search_status->name != $existing_status->name) {
            $_REQUEST['form-errors']['status_label'] = __(
                'Status name conflicts with existing status. Please choose another.',
                'publishpress-statuses'
            );
        }
        // Check to make sure the name is not restricted
        if (self::is_restricted_status(strtolower(sanitize_title($label)))) {
            $_REQUEST['form-errors']['status_label'] = __(
                'Status name is restricted. Please choose another name.',
                'publishpress-statuses'
            );
        }

        // Kick out if there are any errors
        if (count($_REQUEST['form-errors'])) {
            $_REQUEST['error'] = 'form-error';

            return;
        }
        */

        // Try to edit the post status
        $args = [
            'name' => $name,
            'color' => $color,
            'icon' => $icon,
        ];

        if (isset($label)) {
            $args['label'] = $label;
        }

        if (isset($description)) {
            $args['description'] = $description;
        }


        $labels = [];
        $labels['save_as'] = sanitize_text_field($_REQUEST['status_save_as_label']);
        $labels['publish'] = sanitize_text_field($_REQUEST['status_publish_label']);
        $args['labels'] = (object) $labels;


        $status_post_types = !empty($status_obj->post_type) ? $status_obj->post_type : [];

        if (!empty($_REQUEST['pp_status_all_types'])) {
            $args['post_type'] = [];

        } else {
            $set_post_types = !empty($_REQUEST['pp_status_post_types']) ? $_REQUEST['pp_status_post_types'] : false;

            if ($set_post_types) {
                if ($add_types = array_filter(array_map('intval', $set_post_types))) {
                    $status_post_types = array_unique(array_merge($status_post_types, array_map('sanitize_key', array_keys($add_types))));
                }

                if ($remove_types = array_diff(array_map('intval', $set_post_types), ['1', true, 1])) {
                    $status_post_types = array_diff($status_post_types, array_keys($remove_types));
                }

                $args['post_type'] = $status_post_types;
            }
        }

        if (isset($_REQUEST['roles_set_status'])) {
            $cap_name = str_replace('-', '_', "status_change_{$status_obj->name}");
            
            $roles_set_status = array_map('intval', $_REQUEST['roles_set_status']);

            foreach ($roles_set_status as $role_name => $set_val) {
                if (!\PublishPress_Functions::isEditableRole($role_name)) {
                    continue;
                }

                if ($role = get_role($role_name)) {
                    if ($set_val && empty($role->capabilities[$cap_name])) {
                        $role->add_cap($cap_name);
                        $changed = true;

                    } elseif (!$set_val && !empty($role->capabilities[$cap_name])) {
                        $role->remove_cap($cap_name);
                        $changed = true;
                    }
                }

            }

            if (!empty($changed)) {
                \PublishPress_Statuses::updateStatusNumRoles($status_obj->name, ['force_refresh' => true]);
            }
        }

        if (isset($_REQUEST['status_caps'])) {
            foreach ($_REQUEST['status_caps'] as $role_name => $set_status_caps) {
                if (!\PublishPress_Functions::isEditableRole($role_name)) {
                    continue;
                }

                $role = get_role($role_name);

                if ($add_caps = array_diff_key(
                    array_filter($set_status_caps),
                    array_filter($role->capabilities)
                )) {
                    foreach (array_keys($add_caps) as $cap_name) {
                        $role->add_cap($cap_name);
                    }
                }

                $set_false_status_caps = array_diff_key($set_status_caps, array_filter($set_status_caps));

                foreach(array_keys($set_false_status_caps) as $cap_name) {
                    if (!empty($role->capabilities[$cap_name])) {
                        $role->remove_cap($cap_name);
                    }
                }
            }
        }

        $status_obj = get_post_status_object($name);

        if (!empty($_REQUEST['return_module'])) {
            $arr = ['message' => 'status-updated'];
            $arr['page'] = 'pp-modules-settings';
            $arr['settings_module'] = $_REQUEST['return_module'];

            $redirect_url = \PublishPress_Statuses::getLink($arr);
        } else {
            $arr = ['message' => 'status-updated'];
            $arr['page'] = 'publishpress-statuses';

            $arr = apply_filters('publishpress_status_edit_redirect_args', $arr, $status_obj);

            $redirect_url = \PublishPress_Statuses::getLink($arr);
        }

        do_action('publishpress_statuses_edit_status', $existing_status->name, $args);

        $return = self::updateCustomStatus($existing_status->name, $args);

        if (is_wp_error($return)) {
            wp_die(__('Error updating post status.', 'publishpress-statuses'));
        }

        // Saving custom settings for native statuses
        if (!empty($status_obj) && !empty($status_obj->_builtin)) {
            $name = sanitize_title($_GET['label']);

            update_option("psppno_status_{$name}_color", $color);
            update_option("psppno_status_{$name}_icon", $icon);
        }

        wp_redirect($redirect_url);
        exit;
    }


    /**
     * Update an existing custom status
     *
     * @param int @status_id ID for the status
     * @param array $args Any arguments to be updated
     *
     * @return object|WP_Error|false $updated_status Newly updated status object
     */
    public static function updateCustomStatus($name, $args = [])
    {
        if (in_array($name, ['_pre-publish-alternate', '_disabled'])) {
            return;
        }

        $status_obj = \PublishPress_Statuses::getStatusBy('slug', $name);

        if (! $status_obj || is_wp_error($status_obj)) {
            return new \WP_Error('invalid', __("Custom status ($name) doesn't exist.", 'publishpress-statuses'));
        }

        // Reset our internal object cache
        \PublishPress_Statuses::instance()->clearStatusCache();

        /*
            $name = sanitize_title($name);

            // If the slug is empty we need to define one
            if (empty($name)) {
                $name = sanitize_title($args['label']);
            }
        */

        // Reassign posts to new status slug if the slug changed and isn't restricted
        /*
        if ($name && $name != $old_status->name && ! self::is_restricted_status($old_status->name)) {
            self::reassign_post_status($old_status->name, $name);
        }

        $name = !empty($name) ? $name : $old_status->name;
        */

        $updatedStatusId = $name;


        // We're encoding metadata that isn't supported by default in the term's description field
        $args_to_encode = [];

        if (!empty($status_obj->_builtin) && !in_array($status_obj->name, ['pending'])) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_CORE_STATUS;

        } elseif (in_array($name, ['_pre-publish-alternate', '_disabled'])) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_PSEUDO_STATUS;

        } elseif (!empty($status_obj->private)) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_PRIVACY;

        } else {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH;
        }

        // Require some intentionality in adding new properties to the encoded description.
        $properties_to_encode = apply_filters(
            'publishpress_statuses_encoded_properties',
            ['description', 'color', 'icon', 'status_parent', 'post_type', 'labels']
        );

        // Also re-encode any existing properties, since the plugin that defined it may be temporarily deactivated.
        if (!$term = get_term_by('slug', $name, $taxonomy)) {
            if ($term_id = \PublishPress_Statuses::instance()->addStatus($taxonomy, $status_obj->label, ['slug' => $name])) {
                $term = get_term_by('slug', $name, $taxonomy);
                $updated_status_array = (array) $term;
            }
        }

        if ($term) {
            if ($current_properties = \PublishPress_Statuses::get_unencoded_description($term->description)) {
                $properties_to_encode = array_merge($properties_to_encode, array_keys($current_properties));
            }

            if (!empty($status_obj->_builtin)) {
                $properties_to_encode = array_diff($properties_to_encode, ['status_parent', 'post_type']);
            }

            if (in_array($name, ['draft', 'future', 'publish', 'private'])) {
                $properties_to_encode = array_diff($properties_to_encode, ['capability_status']);
            }

            // @todo: review
            if (in_array($name, ['draft', 'future', 'publish', 'private'])) {
                $properties_to_encode = array_diff($properties_to_encode, ['labels']);
            }

            $properties_to_encode = array_unique($properties_to_encode);

            foreach ($properties_to_encode as $prop) {
                if (isset($args[$prop])) {
                    if (is_array($args[$prop])) {
                        foreach ($args[$prop] as $k => $val) {
                            $args_to_encode[$prop][$k] = sanitize_textarea_field($val);
                        }
                    } elseif (is_object($args[$prop])) {
                        $props = \get_object_vars($args[$prop]);

                        foreach($props as $k => $val) {
                            $props[$k] = sanitize_text_field($val);
                        }

                        $args_to_encode[$prop] = (object) $props;
                    } else {
                        $args_to_encode[$prop] = sanitize_textarea_field($args[$prop]);
                    }
                } else {
                    $args_to_encode[$prop] = $status_obj->$prop;
                }
            }

            $args = array_diff_key($args, $args_to_encode);

            $args['description'] = \PublishPress_Statuses::get_encoded_description($args_to_encode);

            if (!empty($args['name'])) {
                $args['slug'] = $args['name'];
            }

            if (!empty($args['label'])) {
                $args['name'] = $args['label'];
            }

            // temp (@todo: test status slug rename
            // if (!empty($status_obj->_builtin)) {
            unset($args['slug']);
            //}

            if (!empty($status_obj->_builtin)) {
                $args['name'] = $status_obj->label;
            }

            $updated_status_array = wp_update_term($term->term_id, $taxonomy, $args);

            if (is_wp_error($updated_status_array)) {
                return $updated_status_array;
            }
        }

        if (!$term || !is_array($updated_status_array) || !isset($updated_status_array['term_id'])) {
            $term_id = (!empty($term)) ? $term->term_id : 0;

            error_log(
                sprintf(
                    '[PUBLISHPRESS] Error updating the status term. $status_id: %s, taxonomy: %s, $args: %s',
                    $term_id,
                    $taxonomy,
                    print_r($args, true)
                )
            );

            return new \WP_Error('custom-status-term_id', esc_html__("Error while updating the status ($name)", 'publishpress-statuses'));
        }

        $updatedStatusId = $updated_status_array['term_id'];

        return \PublishPress_Statuses::getStatusBy('id', $updatedStatusId);
    }

    private function statusDeleted($status_name, $reassign_status = '') {

    }


    /**
     * Deletes a custom status from the wp_terms table.
     *
     * Reassigns posts that currently have the deleted status assigned.
     */
    public static function deleteCustomStatus($old_status, $args = [], $reassign_status = '')
    {
        if ($reassign_status == $old_status) {
            return new \WP_Error('invalid', __('Cannot reassign to the status you want to delete', 'publishpress-statuses'));
        }

        // Reset our internal object cache
        \PublishPress_Statuses::instance()->clearStatusCache();

        if (! self::is_restricted_status($old_status)) {
            $default_status = \PublishPress_Statuses::DEFAULT_STATUS;
            
            // If new status in $reassign, use that for all posts of the old_status
            if (!empty($reassign_status)) {
                if ($_status = \PublishPress_Statuses::getStatusBy('id', $reassign_status)) {
                    $new_status = $_status->name;
                }
            }

            if (empty($new_status)) {
                $new_status = $default_status;
            }
            
            if ($old_status == $default_status) {
                $new_status = 'draft';

                // @todo: If we support a custom default status, set it to draft
            }

            self::reassign_post_status($old_status, $new_status);

            if (!$status_obj = \PublishPress_Statuses::getStatusBy('name', $old_status)) {
                return false;
            }

            if (!empty($status_obj->private)) {
                $taxonomy = \PublishPress_Statuses::TAXONOMY_PRIVACY;
            } else {
                $taxonomy = \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH;
            }

            if ($term = get_term_by('slug', $old_status, $taxonomy)) {
                return wp_delete_term($term->term_id, $taxonomy, $args);
            }

        } else {
            return new \WP_Error(
                'restricted',
                __('Restricted status ', 'publishpress-statuses') . '(' . \PublishPress_Statuses::getStatusBy(
                    'id',
                    $old_status
                )->label . ')'
            );
        }
    }

    public static function handleAjaxToggleStatusSection() {
        global $current_user;
        
        // low capability requirement since this is just a convenience toggle
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!empty($_REQUEST['status_section'])) {
            if (!$collapsed_sections = \get_user_meta($current_user->ID, 'publishpress_statuses_collapsed_sections', true)) {
                $collapsed_sections = [];
            }

            $section = str_replace('status_row_', '', sanitize_key($_REQUEST['status_section']));
            $is_collapsed = !empty($_REQUEST['collapse']);
            
            if ($is_collapsed) {
                $collapsed_sections[$section] = true;
            } else {
                unset($collapsed_sections[$section]);
            }

            \update_user_meta($current_user->ID, 'publishpress_statuses_collapsed_sections', $collapsed_sections);
        }
    }

    public static function handleAjaxDeleteStatus() {
        if (!empty($_REQUEST['delete_status'])) {
            if (! current_user_can('manage_options')) {
                self::printAjaxResponse('error', esc_html__('You are not permitted to do that.', 'publishpress-statuses'));
            }

            $status_name = sanitize_key($_REQUEST['delete_status']);

            if ($status = \PublishPress_Statuses::getStatusBy('slug', $status_name)) {
                if (!empty($status->_builtin) || !empty($status->pp_builtin)) {
                    self::printAjaxResponse('error', esc_html__('You are not permitted to do that.', 'publishpress-statuses'));
                    return;
                }
                
                $return = self::deleteCustomStatus($status_name);

                if (is_wp_error($return)) {
                    self::printAjaxResponse('error', __('Could not delete the status: ', 'publishpress-statuses'));
                } else {
                    self::printAjaxResponse('success', __('Status deleted', 'publishpress-statuses'));
                }
            } else {
                self::printAjaxResponse('error', esc_html__('Status does not exist.', 'publishpress-statuses'));
            }
        }
    }

    /**
     * Handle an ajax request to update the order of custom statuses
     *
     * @since 0.7
     */
    public static function handleAjaxUpdateStatusPositions()
    {
        check_ajax_referer('custom-status-sortable');

        if (! current_user_can('manage_options')) {
            self::printAjaxResponse('error', esc_html__('You are not permitted to do that.', 'publishpress-statuses'));
        }

        if (! isset($_POST['status_positions']) || ! is_array($_POST['status_positions'])) {
            self::printAjaxResponse('error', __('Status positions were not sent.', 'publishpress-statuses'));
        }

        update_option('publishpress_status_positions', array_map('sanitize_key', array_values(array_filter($_POST['status_positions']))));

        // @todo: update 'publishpress_disabled_statuses' based on ordering relative to '_disabled'

        if (!empty($_REQUEST['status_hierarchy'])) {
            $status_parents = [];

            foreach ($_REQUEST['status_hierarchy'] as $position => $arr) {
                $status_name = str_replace('status_row_', '', $arr['id']);
                
                $status_parents[$status_name] = '';
                
                if (!empty($arr['children']) && !empty($status_name)) {
                    foreach ($arr['children'] as $child_arr) {
                        $child_status_name = str_replace('status_row_', '', $child_arr['id']);

                        $status_obj = get_post_status_object($child_status_name);
                        if (!empty($status_obj) && !empty($status_obj->private)) {
                            continue;
                        }

                        $status_parents[$child_status_name] = $status_name;
                    }
                }
            }

            $statuses = \PublishPress_Statuses::getPostStati(
                [], 
                ['output' => 'object', 'context' => 'load'], 
                ['show_disabled' => true]
            );

            // Update any modified status_parent value as an encoded value in term description field
            foreach ($status_parents as $status_name => $edited_status_parent) {
                $current_status_parent = (!empty($statuses[$status_name]) && !empty($statuses[$status_name]->status_parent)) 
                ? $statuses[$status_name] : '';

                if ($edited_status_parent == $current_status_parent) {
                    continue; // no change
                }

                $result = self::updateCustomStatus($status_name, ['status_parent' => $edited_status_parent]);

                if (is_wp_error($result)) {
                    self::printAjaxResponse('error', $result->get_error_message());
                }
            }
        }
        
        self::printAjaxResponse('success', __('Status order updated', 'publishpress-statuses'));
    }

    /**
     * Take a status and a message, JSON encode and print
     *
     * @param string $status Whether it was a 'success' or an 'error'
     * @param string $message
     * @param array $data
     *
     * @since 0.7
     *
     */
    public static function printAjaxResponse($status, $message = '', $data = null)
    {
        \PublishPress_Functions::printAjaxResponse($status, $message, $data);
    }

    /**
     * Determines whether the slug indicated belongs to a restricted status or not
     *
     * @param string $slug Slug of the status
     *
     * @return bool $restricted True if restricted, false if not
     */
    private static function is_restricted_status($slug)
    {
        switch ($slug) {
            case 'publish':
            case 'private':
            case 'future':
            case 'pending':
            case 'draft':
            case 'new':
            case 'inherit':
            case 'auto-draft':
            case 'trash':
                $restricted = true;
                break;

            default:
                $restricted = false;
                break;
        }

        return $restricted;
    }


    /**
     * Validate input from the end user
     *
     * @since 0.7
     */
    public static function settings_validate($new_options)
    {
        // Whitelist validation for the post type options
        if (! isset($new_options['post_types'])) {
            $new_options['post_types'] = [];
        }

        $new_options['post_types'] = self::clean_post_type_options(
            $new_options['post_types'],
            'pp_custom_statuses'
        );

        return $new_options;
    }

    /**
     * Sanitize submitted post types and apply add_post_type_support() usage.
     * 
     * If add_post_type_support() has been used anywhere (legacy support), inherit the state
     *
     * @param array $module_post_types Current state of post type options for the module
     * @param string $post_type_support_slug What the feature is called for post_type_support (e.g. 'pp_calendar')
     *
     * @return array $normalized_post_type_options The setting for each post type, normalized based on rules
     *
     */
    public static function clean_post_type_options($module_post_types = [], $post_type_support_slug = null)
    {
        $normalized_post_type_options = [];

        foreach (array_keys(\PublishPress_Statuses::instance()->get_supported_post_types()) as $post_type) {
            $normalized_post_type_options[$post_type] = !empty($module_post_types[$post_type]) || post_type_supports($post_type, $post_type_support_slug);
        }

        return $normalized_post_type_options;
    }

    public static function settings_validate_and_save()
    {
        if (!isset($_POST['action'], $_POST['_wpnonce'], $_POST['option_page'], $_POST['_wp_http_referer'], $_POST['publishpress_module_name'], $_POST['submit']) || !is_admin()) {
            return false;
        }
        
        if (($_POST['action'] != 'update') || 
        (!in_array('publishpress_statuses', (array)$_POST['publishpress_module_name']))
        ) {
            return false;
        }

        if (!current_user_can('manage_options') || !wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'edit-publishpress-settings')) {
            wp_die(__('Cheatin&#8217; uh?'));
        }

        $module = \PublishPress_Statuses::instance();
        $module_name = 'custom_status';

        $new_options = (isset($_POST[\PublishPress_Statuses::SETTINGS_SLUG])) ? $_POST[\PublishPress_Statuses::SETTINGS_SLUG] : [];
        $new_options = self::settings_validate($new_options);

        // New way to validate settings
        $new_options = apply_filters('publishpress_validate_module_settings', $new_options, $module_name);

        // Cast our object and save the data.
        $new_options = (object)array_merge((array)$module->options, $new_options);

        update_option('publishpress_' . $module_name . '_options', $new_options);
        
        // Redirect back to the settings page that was submitted without any previous messages
        $goback = add_query_arg('message', 'settings-updated', remove_query_arg(['message'], wp_get_referer()));
        wp_safe_redirect($goback);

        exit;
    }

    /**
     * Assign new statuses to posts using value provided or the default
     *
     * @param string $old_status Slug for the old status
     * @param string $new_status Slug for the new status
     */
    private static function reassign_post_status($old_status, $new_status = '')
    {
        global $wpdb;

        if (empty($new_status)) {
            $new_status = \PublishPress_Statuses::DEFAULT_STATUS;
        }

        $result = $wpdb->update(
            $wpdb->posts,
            ['post_status' => $new_status],
            ['post_status' => $old_status],
            ['%s']
        );
    }
}