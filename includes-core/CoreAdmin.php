<?php

namespace PublishPress\Statuses;

class CoreAdmin
{
    function __construct()
    {
        add_action('admin_menu', [$this, 'actAdminMenu'], 22);

        add_action('admin_print_scripts', [$this, 'setUpgradeMenuLink'], 50);

        add_action('publishpress_statuses_settings_sidebar', [$this, 'settingsSidebar']);
        add_filter('publishpress_statuses_settings_sidebar_class', function($class) {return 'has-right-sidebar';});

        if (class_exists('PPVersionNotices\Module\TopNotice\Module')) {
            add_filter(\PPVersionNotices\Module\TopNotice\Module::SETTINGS_FILTER, function ($settings) {
                $settings['publishpress-statuses'] = [
                    'message' => esc_html__("You're using PublishPress Statuses Free. The Pro version has more features and support. %sUpgrade to Pro%s", 'publishpress-statuses'),
                    'link'    => 'https://publishpress.com/links/statuses-banner',
                    'screens' => [
                        ['base' => 'toplevel_page_publishpress-statuses'],
                        ['base' => 'statuses_page_publishpress-statuses-add-new'],
                        ['base' => 'statuses_page_publishpress-statuses-settings'],
                    ]
                ];

                return $settings;
            });
        }
    }

    function actAdminMenu()
    {
        add_submenu_page(
            'publishpress-statuses',
            esc_html__('Upgrade to Pro', 'press-permit-core'),
            esc_html__('Upgrade to Pro', 'press-permit-core'),
            'read',
            'statuses-pro',
            ['PublishPress\Statuses\UI\Dashboard\DashboardFilters', 'actMenuHandler']
        );
    }

    function setUpgradeMenuLink()
    {
        $url = 'https://publishpress.com/links/statuses-menu';
?>
        <style type="text/css">
            #toplevel_page_publishpress-statuses ul li:last-of-type a {
                font-weight: bold !important;
                color: #FEB123 !important;
            }
        </style>

        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function($) {
                $('#toplevel_page_publishpress-statuses ul li:last a').attr('href', '<?php echo esc_url($url); ?>').attr('target', '_blank').css('font-weight', 'bold').css('color', '#FEB123');
            });
            /* ]]> */
        </script>
        <?php
    }

    function settingsSidebar() {
        wp_enqueue_style(
            'pp-wordpress-banners-style',
            plugin_dir_url(PUBLISHPRESS_STATUSES_FILE) . 'lib/vendor/publishpress/wordpress-banners/assets/css/style.css',
            false,
            PP_WP_BANNERS_VERSION
        );
        ?>

        <div class="pp-column-right pp-statuses-sidebar pp-statuses-pro-promo-right-sidebar">

        <?php
        $this->sidebarBannerContent();
        ?>

        </div>
        <?php
    }

    private function sidebarBannerContent() { 
        ?>
        <div class="ppc-advertisement-promo">
            <div class="advertisement-box-content postbox ppc-statuses-pro-promo">
                <div class="postbox-header">
                    <h3 class="advertisement-box-header hndle is-non-sortable">
                        <span><?php echo esc_html__('Upgrade to Statuses Pro', 'publishpress-statuses'); ?></span>
                    </h3>
                </div>
    
                <div class="inside">
                    <p><?php echo esc_html__('Upgrade to PublishPress Statuses Pro for additional benefits:', 'publishpress-statuses'); ?>
                    </p>
                    <ul>
                        <li><?php echo esc_html__('Define revision statuses', 'publishpress-statuses'); ?></li>
                        <li><?php echo esc_html__('Control revision status selection', 'publishpress-statuses'); ?></li>
                        <li><?php echo esc_html__('Customize revision workflow sequence', 'publishpress-statuses'); ?></li>
                        <li><?php echo esc_html__('Custom notifications, with PublishPress Planner', 'publishpress-statuses'); ?></li>
                        <li><?php echo esc_html__('No ads inside the plugin', 'publishpress-statuses'); ?></li>
                        <li><?php echo esc_html__('Prompt, professional support', 'publishpress-statuses'); ?></li>
                    </ul>

                    <div class="upgrade-btn">
                        <a href="https://publishpress.com/links/statuses-banner/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'publishpress-statuses'); ?></a>
                    </div>
                </div>
            </div>
            <div class="advertisement-box-content postbox">
                <div class="postbox-header">
                    <h3 class="advertisement-box-header hndle is-non-sortable">
                        <span><?php echo esc_html__('Need Statuses Support?', 'publishpress-statuses'); ?></span>
                    </h3>
                </div>
    
                <div class="inside">
                    <p><?php echo esc_html__('If you need help or have a new feature request, let us know.', 'publishpress-statuses'); ?>
                        <a class="advert-link" href="https://wordpress.org/support/plugin/publishpress-statuses/" target="_blank">
                        <?php echo esc_html__('Request Support', 'publishpress-statuses'); ?> 
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="linkIcon">
                                <path
                                    d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"
                                ></path>
                            </svg>
                        </a>
                    </p>
                    <p>
                    <?php echo esc_html__('Detailed documentation is also available on the plugin website.', 'publishpress-statuses'); ?> 
                        <a class="advert-link" href="https://publishpress.com/knowledge-base/start-statuses/" target="_blank">
                        <?php echo esc_html__('View Knowledge Base', 'publishpress-statuses'); ?> 
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="linkIcon">
                                <path
                                    d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"
                                ></path>
                            </svg>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
}
