<?php

namespace PublishPress;

class ModuleAdminUI_Base {
    private $module;

    private static $instance = null;

    public static function instance($module = false) {
        if (is_null(self::$instance)) {
            self::$instance = new \PublishPress\ModuleAdminUI_Base($module);
        }

        return self::$instance;
    }

    private function __construct($module) {
        if ($module) {
            $this->module = $module;
        }
    }

    /**
     * Given a form field and a description, prints either the error associated with the field or the description.
     *
     * @param string $field The form field for which to check for an error
     * @param string $description Unlocalized string to display if there was no error with the given field
     *
     *@since 0.7
        *
        */
    public static function print_error_or_description($field, $description)
    {
        if (isset($_REQUEST['form-errors'][$field])): ?>
            <div class="form-error">
                <p><?php echo esc_html($_REQUEST['form-errors'][$field]); ?></p>
            </div>
        <?php else: ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif;
    }

    public function helper_print_error_or_description($field, $description)
    {
        self::print_error_or_description($field, $description);
    }

    public function print_default_header($current_module, $custom_text = null)
    {
        self::default_header($current_module, $custom_text);
    }

    public static function defaultHeader() {
        return self::instance()->default_header();
    }

    public function default_header($custom_text = null)
    {
        $display_text = '';

        // If there's been a message, let's display it
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
        } elseif (isset($_REQUEST['message'])) {
            $message = sanitize_text_field($_REQUEST['message']);
        } elseif (isset($_POST['message'])) {
            $message = sanitize_text_field($_POST['message']);
        } else {
            $message = false;
        }

        if ($message && isset($this->module->messages[$message])) {
            $display_text .= '<div class="is-dismissible notice notice-info"><p>' . esc_html($this->module->messages[$message]) . '</p></div>';
        }

        // If there's been an error, let's display it
        if (isset($_GET['error'])) {
            $error = sanitize_text_field($_GET['error']);
        } elseif (isset($_REQUEST['error'])) {
            $error = sanitize_text_field($_REQUEST['error']);
        } elseif (isset($_POST['error'])) {
            $error = sanitize_text_field($_POST['error']);
        } else {
            $error = false;
        }

        if ($error && isset($this->module->messages[$error])) {
            $display_text .= '<div class="is-dismissible notice notice-error"><p>' . esc_html($this->module->messages[$error]) . '</p></div>';
        }
        ?>

        <div class="publishpress-admin pressshack-admin-wrapper wrap">
            <header>
                <!--
                <div class="pp-icon">
                <img src="<?php echo PUBLISHPRESS_STATUSES_URL . 'common/assets/publishpress-logo-icon.png';?>" alt="" class="logo-header" />
                </div>
                -->

                <h1 class="wp-heading-inline"><?php echo $this->module->title; ?></h1>

                <?php echo !empty($display_text) ? $display_text : ''; ?>
                <?php // We keep the H2 tag to keep notices tied to the header?>
                <h2>
                    <?php if ($this->module->short_description && empty($custom_text)): ?>
                        <?php echo $this->module->short_description; ?>
                    <?php endif; ?>

                    <?php if (!empty($custom_text)) : ?>
                        <?php echo $custom_text; ?>
                    <?php endif; ?>
                </h2>

            </header>
        <?php
    }

}
