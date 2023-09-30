<?php
namespace PublishPress_Statuses;

/**
 * Custom Statuses uses WordPress' List Table API for generating the custom status management table
 *
 * @since 0.7
 */
class StatusListTable extends \WP_List_Table
{
    public $callback_args;

    public $default_status;

    private $module;

    private $collapsed_sections = [];
    private $status_children = [];
    private $current_ancestors = [];

    private $status_roles = [];

    /**
     * Construct the extended class
     */
    public function __construct()
    {
        parent::__construct(
            [
                'plural' => 'statuses',
                'singular' => 'status',
                'ajax' => true,
            ]
        );

        add_action('admin_footer', [$this, 'adminFooterScripts']);

        /*
        if (!empty($_REQUEST['pp_refresh_role_counts'])) {
            delete_option('publishpress_statuses_num_roles');
        }
        */

        if (!$this->status_roles = get_option('publishpress_statuses_num_roles')) {
            $this->status_roles = [];
        }

        $this->get_collapsed_sections();
    }

    function adminFooterScripts() {
        // Prevent disorienting vertical shift of the statuses table caused by standard notice div after drag-ordering a status.
        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function ($) {
            $("div.notice-success").css('float', 'right').insertBefore("div.pp-icon");
        });
        /* ]]> */
        </script>
        <?php
    }

    function get_collapsed_sections() {
        global $current_user;

        if ($collapsed = get_user_meta($current_user->ID, 'publishpress_statuses_collapsed_sections', true)) {
            $this->collapsed_sections = (array) $collapsed;
        }
    }

    /**
     * Pull in the data we'll be displaying on the table
     *
     * @since 0.7
     */
    public function prepare_items()
    {
        global $publishpress;

        $columns = $this->get_columns();
        
        $hidden = apply_filters('publishpress_statuses_hidden_columns', [
            'position',
            'status_name'
        ]);

        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        $this->items = \PublishPress_Statuses::getPostStati(
            [], 
            ['output' => 'object', 'context' => 'load']
        );

        $total_items = count($this->items);

        foreach($this->items as $status) {
            if (!empty($status->status_parent)) {
                if (!isset($this->status_children[$status->status_parent])) {
                    $this->status_children[$status->status_parent] = [];
                }

                $this->status_children[$status->status_parent][] = $status->name;
            }
        }

        $this->default_status = \PublishPress_Statuses::DEFAULT_STATUS;

        $this->set_pagination_args(
            [
                'total_items' => $total_items,
                'per_page' => $total_items,
            ]
        );
    }

    /**
     * Table shows (hidden) position, status name, status description, and the post count for each activated
     * post type
     *
     * @return array $columns Columns to be registered with the List Table
     * @since 0.7
     *
     */
    public function get_columns()
    {
        global $publishpress;

        $columns = [
            'position' => __('Position', 'publishpress-statuses'),
            'status_name' => __('Status Name', 'publishpress-statuses'),
            'name' => __('Name', 'publishpress-statuses'),
            'icon' => __('Icon', 'publishpress-statuses'),
            'roles' => esc_html__('Roles', 'publishpress-statuses'),
            'post_types' => esc_html__('Post Types', 'publishpress-statuses'),
            'description' => __('Description', 'publishpress-statuses'),
        ];

        return apply_filters('publishpress_statuses_admin_columns', $columns);
    }

    public function display() {
        $singular = $this->_args['singular'];

		$this->display_tablenav( 'top' );

        $this->screen->render_screen_reader_content( 'heading_list' );
		?>

<div class="wrap pp-nested-list">

<div class="is-dismissible notice notice-success pp-float-notice" style="clear:both;display:none;"><p></p></div>

<div class="pp-nested-list">
	<div id="status_list_header" class="status-list-header">

        <div class="row tpl-default">
            <div class="check-column">
                <input type="checkbox" data-np-check-all="pp-nested_bulk[]" data-np-bulk-checkbox="">
            </div>

            <div class="child-toggle" style="padding-left: 0"><div class="child-toggle-spacer"></div></div>

            <div class="row-inner">
                <table class="status-row" style="float:right; width:100%"><tbody>
                <tr>

                <?php
                list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

                foreach ($columns as $column_name => $column_display_name) {
                    $is_hidden = in_array($column_name, $hidden, true);?>
                    <th class="<?php echo $column_name;?>" <?php if ($is_hidden) echo 'style="width:0"';?>>
                        <div class="<?php echo $column_name;?> column-<?php echo $column_name;?>" <?php if ($is_hidden) echo 'hidden';?>>
                            <?php echo $column_display_name;?>
                        </div>
                    </th>
                    <?php
                }
                ?>

                </tr>
                </tbody></table>
            </div>
        </div>

    </div>
</div>


<ol class="sortable visible ui-sortable pp-nested-list <?php echo implode( ' ', $this->get_table_classes() ); ?>" id="the_status_list">
	<?php $this->display_rows_or_placeholder(); ?>
</ol>

</div>

<?php  
}
    

    /**
	 * Generates the tbody element for the list table.
	 *
	 * @since 3.1.0
	 */
	public function display_rows_or_placeholder() {
		if ( $this->has_items() ) {
			$this->display_rows();
		} else {
			echo '<li class="no-items colspanchange" ' /*. $this->get_column_count() */ . '>';
			$this->no_items();
			echo '</li>';
		}
	}

	/**
	 * Generates the table rows.
	 *
	 * @since 3.1.0
	 */
	public function display_rows() {
        $this->display_section_row('_pre-publish', 
            [
                'label' => __('Pre-Publication Workflow Sequence:', 'publishpress-statuses'),
                'class' => 'moderation-status'
            ]);

		foreach ( $this->items as $item ) {
			$this->single_row( $item );
		}
    }
    
    private function display_section_row($key, $args) {
        $class = (!empty($args['class'])) ? $args['class'] : '';
        $label = (!empty($args['label'])) ? $args['label'] : $key;
        ?>
<li id="status_row_<?php echo esc_attr($key);?>" class="page-row section-row <?php echo esc_attr($class);?>">
<div class="row tpl-default">
<div class="row-inner has-row-actions">

<table class="status-row" style="float:right; width:100%"><tbody><tr>

<?php 
if (!empty($this->collapsed_sections[$key])):?>
<script type="text/javascript">
    /* <![CDATA[ */
    jQuery(document).ready(function ($) {
        setTimeout(function() {
            $('#the_status_list #status_row_<?php echo $key;?> div.section-toggle a').trigger('click');
        }, 10);
    });
    /* ]]> */
</script>
<?php endif;?>

<td class="section-toggle"><div class="section-toggle">
<a href="#" class="open"><span class="pp-icon-arrow"></span></a>
</div></td>

<?php if (\PublishPress_Statuses::getCustomStatus($key)) :?>
<td class="status_name" style="width:0"><div class="status_name <?php echo $key;?> column-<?php echo $key;?> hidden"><?php echo $key;?></div></td>
<?php endif; ?>

<td class="name"><div class="name column-name has-row-actions column-primary" data-colname="Name"><strong><?php echo $label;?></strong>

<?php if (in_array($key, ['_pre-publish'])):
    $url = \PublishPress_Statuses::getLink(['action' => 'add-new']);
    ?>
    <button type="button" class="add-new" title="<?php _e("Add New Pre-Publication Status", 'publishpress-statuses');?>" onclick="window.location.href='<?php echo $url;?>'">+</button>
<?php endif;

do_action('publishpress_statuses_admin_row', $key, []);
?>
</div>
</td>

</tr></tbody></table>
</div></div></li>
        <?php
    }

	/**
	 * Generates content for a single row of the table.
	 *
	 * @since 3.1.0
	 *
	 * @param object|array $item The current item
	 */
	public function single_row( $item ) {
        $last_status_parent = end($this->current_ancestors);

        if (empty($this->status_children[$item->name]) || ($item->status_parent != $last_status_parent) ) :
            $item_status_parent = (!empty($item->status_parent)) ? $item->status_parent : '';

            if ($last_status_parent && ($last_status_parent != $item_status_parent)) :
                // Close the nested list and parent status
                array_pop($this->current_ancestors);
                ?>
                </ol>
                </li>
            <?php
            endif;
            ?>
        <?php 
        endif;?>
        <?php
        
        if ('future' == $item->name) {
            $this->display_section_row('_standard-publication', 
            [
                'label' => __('Standard Publication:', 'publishpress-statuses'),
                'class' => ''
            ]);
        
        } elseif ('private' == $item->name) {
            $this->display_section_row('_visibility-statuses', 
            [
                'label' => __('Visibility Statuses for Private Publication:', 'publishpress-statuses'),
                'class' => 'private-status'
            ]);

        } elseif ('_pre-publish-alternate' == $item->name) {
            $this->display_section_row('_pre-publish-alternate', 
            [
                'label' => __('Manually Selectable Pre-Publication Statuses (may be dragged to desired sequence position):', 'publishpress-statuses'),
                'class' => 'alternate-moderation-status'
            ]);

            return;

        } elseif ('_disabled' == $item->name) {
            $this->display_section_row('_disabled', 
            [
                'label' => sprintf(
                        // translators: %s is the opening and closing <span> tags
                    __('Disabled Statuses %1$s(drag to re-enable)%2$s:', 'publishpress-statuses'),
                    '<span class="pp-status-ordering-note">',
                    '</span>'
                ),
                'class' => 'disabled-status'
            ]);

            return;
        }
        
        if (!empty($item->alternate) && ('future' != $item->name)) {
            $class = ' alternate-moderation-status';
        } elseif ((!empty($item->moderation) || ('draft' == $item->name)) && ('future' != $item->name)) {
            $class = ' moderation-status';
        } elseif (!empty($item->private)) {
            $class = ' private-status';
        } else {
            $class = '';
        }

        if (!empty($item->disabled)) {
            $class .= ' disabled-status';
        }

        if (in_array($item->name, ['_pre-publish-alternate', '_disabled'])) {
            $class .= ' section-row';
        }
        ?>
        <li id="status_row_<?php echo $item->name;?>" class="page-row<?php echo $class;?>">

        <div class="row tpl-default">
            <div class="check-column">
                <input id="cb-select-<?php echo $item->name;?>" type="checkbox" name="status[]" value="<?php echo $item->name;?>" />
            </div>

            <div class="child-toggle" style="padding-left: 0">
                <div class="child-toggle-spacer"></div>
            </div>

            <div class="row-inner has-row-actions">
                <?php
                $this->single_row_columns( $item );
                ?>
            </div>
        </div>

        <?php 
        if (!empty($this->status_children[$item->name])) :
            // Open a nested list instead of closing this item
            $this->current_ancestors []= $item->name;
            ?>
            <ol class="pp-nested-list" style="display: block">
            <?php
        else:
            // Normal closure of this item
            ?>
            </li>
        <?php 
        endif;?>
        <?php
	}

	/**
	 * @param object|array $item
	 * @param string $column_name
	 */
	protected function column_default( $item, $column_name ) {}

	/**
	 * @param object|array $item
	 */
	protected function column_cb( $item ) {}

	/**
	 * Generates the columns for a single row of the table.
	 *
	 * @since 3.1.0
	 *
	 * @param object|array $item The current item.
	 */
	protected function single_row_columns( $item ) {
        ?>
        <table class="status-row" style="float:right; width:100%"><tr>

        <?php
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
            if (!in_array($column_name, ['name']) && !empty($item->taxonomy) && (\PublishPress_Statuses::TAXONOMY_PSEUDO_STATUS == $item->taxonomy)) {
                continue;
            }

			$classes = "$column_name column-$column_name";
			if ( 'name' === $column_name ) {
                $classes .= ' has-row-actions column-primary';
            }

			if ( in_array( $column_name, $hidden, true ) ) {
				$classes .= ' hidden';
            }
            
			// Comments column uses HTML in the display name with screen reader text.
			// Strip tags to get closer to a user-friendly string.
			$data = 'data-colname="' . esc_attr( wp_strip_all_tags( $column_display_name ) ) . '"';

			$attributes = "class='$classes' $data";

            echo '<td class="' . $column_name . '" ';
            echo ( in_array( $column_name, $hidden, true ) ) ? 'style="width:0"' : '';
            echo '>';

			if ( 'cb' === $column_name ) {
				echo '<div scope="row" class="check-column">';
				echo $this->column_cb( $item );
                echo '</div>';
                
            } elseif ( 'status_name' === $column_name ) {
                echo "<div $attributes>";
                echo $item->name;
                echo '</div>';

            } elseif ('post_types' == $column_name) {
                echo "<div $attributes>";
                
                if (!in_array($item->name, ['draft', 'pending', 'future', 'publish', 'private'])) {
                    $status_obj = $item; // get_post_status_object($item->name);

                    if (!empty($status_obj) && !empty($status_obj->post_type)) {
                        $arr_captions = [];
                        foreach ($status_obj->post_type as $_post_type) {
                            if ($type_obj = get_post_type_object($_post_type)) {
                                $arr_captions [] = $type_obj->labels->singular_name;
                            }
                        }

                        $types_caption = implode(', ', array_slice($arr_captions, 0, 7));

                        if (count($arr_captions) > 7) {
                            // translators: %s is the list of post types
                            $types_caption = sprintf(esc_html__('%s, more...', 'publishpress-statuses'), esc_html($types_caption));
                        } else {
                            $types_caption = esc_html($types_caption);
                        }
                    } else {
                        $types_caption = esc_html('All');
                    }

                    $url = admin_url("admin.php?action=edit-status&name={$item->name}&page=publishpress-statuses&pp_tab=post_types");
                    $types_link = "<a href='$url'>$types_caption</a>";

                    echo $types_link;
                } else {
                    esc_html_e('All');
                }

                echo '</div>';

            } elseif ( 'roles' === $column_name ) {
                // $this->role_caps

                if (!in_array($item->name, ['draft', 'future', 'publish', 'private'])) {
                    echo "<div $attributes>";

                    if (!isset($this->status_roles[$item->name])) {
                        $this->status_roles[$item->name] = \PublishPress_Statuses::updateStatusNumRoles($item->name);
                    }

                    $num_roles = isset($this->status_roles[$item->name]) ? $this->status_roles[$item->name] : 0; // @todo

                    $url = admin_url("admin.php?action=edit-status&name={$item->name}&page=publishpress-statuses&pp_tab=roles");
                    $roles_link = "<a href='$url'>$num_roles</a>";

                    echo $roles_link;
                    echo '</div>';
                }

            } elseif ('enabled' == $column_name) {
                echo "<div $attributes>";

                if (in_array($item->name, ['draft', 'future', 'publish', 'private'])) {
                    esc_html_e('(Standard)', 'publishpress-statuses');
                } else {
                    $status_obj = $item;

                    if (!empty($disabled_conditions[$item->name])) {
                        $caption = esc_html('Disabled', 'publishpress-statuses');
                
                    } elseif (in_array($item->name, ['pending']) || ! empty($status_obj->moderation) || ! empty($status_obj->private)) {
                        //if (!\PublishPress\Permissions\Statuses::postStatusHasCustomCaps($item->name)) {
                        if (empty($status_obj->capability_status)) {
                            $caption = esc_html('(Standard)', 'publishpress-statuses');
                        } else {
                            if (!empty($status_obj->capability_status) && ($status_obj->capability_status != $status_obj->name)) {
                                if ($cap_status_obj = get_post_status_object($status_obj->capability_status)) {
                                    // translators: %s is the name of the status that has the same capabilities
                                    $caption = sprintf(esc_html__('(same as %s)', 'publishpress-statuses'), esc_html($cap_status_obj->label));
                                } else {
                                    $caption = esc_html('Custom', 'publishpress-statuses');
                                }
                            } else {
                                $caption = esc_html('Custom', 'publishpress-statuses');
                            }
                        }
                    } else {
                        $caption = esc_html('(Standard)', 'publishpress-statuses');
                    }

                    $url = admin_url("admin.php?action=edit-status&name={$item->name}&page=publishpress-statuses&pp_tab=post_access");
                    echo "<a href='$url'>$caption</a>";
                }

                echo '</div>';

			} elseif ( method_exists( $this, '_column_' . $column_name ) ) {
                echo call_user_func(
					array( $this, '_column_' . $column_name ),
					$item,
					$classes,
					$data,
					$primary
                );
			} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
                echo "<div $attributes>";

                echo call_user_func( array( $this, 'column_' . $column_name ), $item );

                echo '</div>';
			} else {
				echo "<div $attributes>";
                //echo $this->column_default( $item, $column_name );
                echo esc_html(apply_filters('presspermit_manage_conditions_custom_column', '', $column_name, 'post_status', $item->name));
                echo '</div>';
            }

            echo '</td>';
        }
        ?>

        </tr></table>

        <div style="float:left"></div>
		
        <div style="float:left">

        </div>
        
        <?php
    }
    

    public function get_table_classes()
    {
        $classes_list = parent::get_table_classes();
        $class_to_remove = 'fixed';

        $class_to_remove_index = array_search($class_to_remove, $classes_list);
        if ($class_to_remove_index === false) {
            return $classes_list;
        }

        unset($classes_list[$class_to_remove_index]);

        return $classes_list;
    }

    /**
     * Message to be displayed when there are no custom statuses. Should never be displayed, but we'll customize it
     * just in case.
     *
     * @since 0.7
     */
    public function no_items()
    {
        _e('No custom statuses found.', 'publishpress-statuses');
    }

    /**
     * Hidden column for storing the status position
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 0.7
     *
     */
    public function column_position($item)
    {
        return esc_html($item->position);
    }

    /**
     * Displayed column showing the name of the status
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 0.7
     *
     */
    public function column_name($item)
    {
        global $publishpress;

        if (!empty($item->taxonomy) && (\PublishPress_Statuses::TAXONOMY_PSEUDO_STATUS == $item->taxonomy)) {
            return $this->pseudo_status_column_name($item);
        }

        //if () { / @todo: list statuses without editing ability?
            $item_edit_link = esc_url(
                \PublishPress_Statuses::getLink(
                    [
                        'action' => 'edit-status',
                        'name' => $item->name,
                        //'status' => $item->name,
                    ]
                )
            );
        //} else {
           // $item_edit_link = '';
        //}
        
        $status_obj = $item;

        $handle_class = (!empty($status_obj) && empty($status_obj->public) && !in_array($status_obj->name, ['draft', 'future', 'private']))
        ? 'handle '
        : 'handle-disabled ';
        
        $output = "<img src='" . PUBLISHPRESS_STATUSES_URL . "common/assets/handle.svg' alt='Sorting Handle' class='{$handle_class}np-icon-menu'>";

        $output .= '<span class="pp-statuses-color" style="background:' . esc_attr($item->color) . ';"></span>';

        $output .= '<strong>';
        if (empty($item->_builtin)) {
            $output .= '<em>';
        }

        if ($item_edit_link) {
            $output .= '<a href="' . $item_edit_link . '">';
        }

        $output .= esc_html($item->label);
        
        if ($item_edit_link) {
            $output .= '</a>';
        }

        if ($item->name == $this->default_status) {
            $output .= ' - ' . __('Default', 'publishpress-statuses');
        }
        if (empty($item->_builtin)) {
            $output .= '</em>';
        }
        $output .= '</strong>';

        $actions = [];
        //$actions['edit'] = "<a href='$item_edit_link'>" . __('Edit', 'publishpress-statuses') . "</a>";

        $status_obj = $item;

        if (empty($status_obj) || (empty($status_obj->_builtin))) {
            $actions['disable'] = '<a href="#">' . __('Disable', 'publishpress-statuses') . '</a>';
        }

        if (empty($status_obj) || (empty($status_obj->_builtin) && empty($status_obj->pp_builtin))) {
            $actions['delete'] = '<a href="#">' . __('X', 'publishpress-statuses') . '</a>';
        }

        $actions = apply_filters('publishpress_statuses_row_actions', $actions, $item);
        
        $output .= $this->row_actions($actions, false);

        return $output;
    }

    public function pseudo_status_column_name($item)
    {
        $output = '<strong><em>';
        $output .= esc_html($item->label);
        $output .= '</em></strong>';

        return $output;
    }

    protected function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );

		if ( ! $action_count ) {
			return '';
		}

        $out = '';

		$mode = get_user_setting( 'posts_list_mode', 'list' );

		if ( 'excerpt' === $mode ) {
			$always_visible = true;
		}

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';

		$i = 0;

		foreach ( $actions as $action => $link ) {
			++$i;
			$sep = ( $i < $action_count ) ? ' | ' : '';
			$out .= "<span class='" . esc_attr($action) . "'>" . $link . esc_html($sep) . "</span>";
		}

        $out .= '</div>';

        return $out;
	}

    /**
     * Displayed column showing the description of the status
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 0.7
     *
     */
    public function column_description($item)
    {
        $descript = esc_html(!empty($item->description) ? $item->description : '&nbsp;');

        $url = admin_url("admin.php?action=edit-status&name={$item->name}&page=publishpress-statuses");
        $link = "<a href='$url'>$descript</a>";

        return $link;
    }

    /**
     * Displayed column showing the icon of the status
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 1.7.0
     *
     */
    public function column_icon($item)
    {
        $icon = '<span class="dashicons ' . esc_html($item->icon) . '"></span>';

        $url = admin_url("admin.php?action=edit-status&name={$item->name}&page=publishpress-statuses");
        $link = "<a href='$url'>$icon</a>";

        return $link;
    }
}
