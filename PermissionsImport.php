<?php

namespace PublishPress_Statuses;

class PermissionsImport {
    private static function term_exists($name, $terms) {
        foreach ($terms as $term) {
            if ($name == $term->name) {
                return $term;
            }
        }

        return false;
    }

    public static function import($terms) {
        // migrate status_parent from Permissions Pro
        $status_parent = get_option('presspermit_status_parent');
            
        if ($status_parent && is_array($status_parent)) {
            foreach ($terms as $k => $term) {
                if (!empty($status_parent[$term->name])) {
                    if (self::term_exists($status_parent[$term->name], $terms)) {
                        update_term_meta($term->term_id, 'status_parent', $status_parent[$term->name]);
                    }
                }
            }
        } else {
            $status_parent = [];
        }

        // If status order has already been stored by PP Statuses, don't re-apply default order from Permissions Pro Status Control
        if (get_option('publishpress_status_positions')) {
            return;
        }

        // migrate status order from Permissions Pro (needs more work to account for status type boundaries)
        if ($_presspermit_status_order = get_option('presspermit_status_order')) {
            update_option('publishpress_statuses_status_control_import', PUBLISHPRESS_STATUSES_VERSION);

            // Merge in Permissions Pro default ordering values
            $_presspermit_status_order = array_merge(
                [
                'draft' => 0,
                'pitch' => 2,
                'assigned' => 5,
                'in-progress' => 7,
                'pending' => 10,
                'approved' => 18
                ],
                $_presspermit_status_order
            );

            asort($_presspermit_status_order);

            // Account for status parents
            $presspermit_status_positions = [];

            foreach ($_presspermit_status_order as $status => $order) {
                if (empty($status_parent[$status])) {
                    $presspermit_status_positions[] = $status;

                    foreach ($_presspermit_status_order as $child_status => $order) {
                        if (!empty($status_parent[$child_status]) && ($status == $status_parent[$child_status])) {
                            $presspermit_status_positions[] = $child_status;
                        }
                    }
                }
            }

            $statuses = \PublishPress_Statuses::instance()->getPostStatuses([], 'names', ['load' => true, 'show_disabled' => true, 'skip_archive' => true]);

            // As of v1.0, our stored array always starts with nullstring (for Pre-Pub Workflow header), then Draft status
            $new_status_positions = ['', 'draft'];

            // Add any Permissions-stored workflow statuses
            foreach ($presspermit_status_positions as $status) {
                if ($status_obj = in_array($status, $statuses)) {
                    if (empty($status_obj->public) && empty($status_obj->private) && !in_array($status, ['draft', 'future'])) {
                        $new_status_positions []= $status;
                    }
                }
            }

            $new_status_positions []= '_pre-publish-alternate';

            $disabled_statuses = [];

            // Add any other Statuses-stored statuses as alternate workflow
            foreach ($statuses as $status) {
                if (in_array($status, $new_status_positions)) {
                    continue;
                }
                
                // ignore statuses positioned after this marker
                if ('_disabled' == $status) {
                    $in_disabled_statuses = true;
                    continue;
                }

                if (!empty($in_disabled_statuses)) {
                    $disabled_statuses []= $status;
                    continue;
                }

                if (empty($status_obj->public) && empty($status_obj->private) && ('future' != $status)) {
                    $new_status_positions []= $status;
                }
            }

            // Add standard published, private, future statuses (with separators as in Ajax update)
            $new_status_positions = array_merge(
                $new_status_positions,
                [
                    '',
                    'future',
                    'publish',
                    '',
                    'private'
                ]
            );

            // Add any other Statuses-stored Visibility Statuses
            foreach ($statuses as $status) {
                // ignore statuses positioned after this marker
                if ('_disabled' == $status) {
                    break;
                }

                if (!in_array($status, $new_status_positions)) {
                    if ($status_obj = get_post_status_object($status)) {
                        if ((!empty($status_obj->private)) && ('private' != $status)) {
                            $new_status_positions []= $status;
                        }
                    }
                }
            }

            $new_status_positions []= '_disabled';

            $new_status_positions = array_merge(
                $new_status_positions,
                $disabled_statuses
            );

            update_option('publishpress_status_positions', $new_status_positions);

            update_option('publishpress_statuses_status_control_positions_import', PUBLISHPRESS_STATUSES_VERSION);
        }
    }
}
