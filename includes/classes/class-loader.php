<?php

class Wooventory
{

    /**
     * Plugin activation
     * @return void
     */
    public static function activate()
    {
        self::checkRequirements();
    }

    /**
     * Check plugin requirements
     * @return void
     */
    private static function checkRequirements()
    {
        delete_option('rms-zi-admin-error');

        //Detect WooCommerce plugin
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            //Load the plugin's translated strings
            // load_plugin_textdomain('rmsZI', false, dirname(RMS_BASENAME) . '/languages');

            $error = '<strong>' . sprintf(__('%1$s %2$s requires WooCommerce Plugin to be installed and activated.', 'rmsZI'), RMS_PLUGIN_NAME, RMS_VERSION) . '</strong> ' . sprintf(__('Please <a href="%1$s" target="_blank">install WooCommerce Plugin</a>.', 'rmsZI'), 'https://wordpress.org/plugins/woocommerce/');

            update_option('rms-zi-admin-error', $error);
        }
    }

    /**
     * Initialize WordPress hooks
     * @return void
     */
    public static function initHooks()
    {
        //Init
        add_action('init', array('wooventory', 'init'));

        //Admin init
        add_action('admin_init', array('wooventory', 'adminInit'));

        //Admin notices
        add_action('admin_notices', array('wooventory', 'adminNotices'));
        add_action('wp_ajax_dismiss_rmszi_review_request_notice', array('wooventory', 'dismiss_rmszi_review_request_notice'));
        add_action('wp_ajax_skip_rmszi_review_request_notice', array('wooventory', 'skip_rmszi_review_request_notice'));

        //Plugins page
        add_filter('plugin_row_meta', array('wooventory', 'pluginRowMeta'), 10, 2);
        add_filter('plugin_action_links_' . RMS_BASENAME, array('wooventory', 'actionLinks'));

        //Admin page
        $page = filter_input(INPUT_GET, 'page');
        if (!empty($page) && $page == RMS_MENU_SLUG) {
            add_filter('admin_footer_text', array('wooventory', 'adminFooter'));
        }
    }

    /**
     * Init
     * @return void
     */
    public static function init()
    {
        //Load the plugin's translated strings
        // load_plugin_textdomain('rmsZI', false, dirname(RMS_BASENAME) . '/languages');
    }

    /**
     * Admin init
     * @return void
     */
    public static function adminInit()
    {
        // Check plugin requirements
        self::checkRequirements();
    }

    /**
     * Admin notices
     * @return void
     */
    public static function adminNotices()
    {
        if (get_option('rms-zi-admin-error')) {
            $class = 'notice notice-error';
            $message = get_option('rms-zi-admin-error');

            printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
        }
        self::output_review_request_link();
    }

    /**
     * Place review output
     * @return void
     */
    public static function output_review_request_link()
    {

        $is_dismissed = get_transient('rmszi_review_request_notice_dismissed');
        if ($is_dismissed) {
            return;
        }

        $is_skipped = get_transient('rmszi_skip_review_request_notice');
        if ($is_skipped) {
            return;
        }

        $rmszi_since = get_option('rmszi_since');
        $now = time();
        if (!$rmszi_since) {
            update_option('rmszi_since', $now, 'no');
        } else {
            $diff_seconds = $now - $rmszi_since;

            if ($diff_seconds > apply_filters('rmszi_show_review_request_notice_after', 10 * DAY_IN_SECONDS)) {
                self::render_review_request_notice();
            }
        }
        //If you find this plugin useful please show your support and rate it ★★★★★ on WordPress.org - much appreciated! :)
    }

    public static function render_review_request_notice()
    {
        $review_url = "https://wooventory.com/product/wooventory";
        ?>
        <div id="rmszi_review_request_notice" class="notice notice-info is-dismissible thpladmin-notice" data-nonce="<?php echo wp_create_nonce('rmszi_review_request_notice'); ?>" data-action="dismiss_rmszi_review_request_notice" style="display:none">
            <h3>
                Just wanted to say thank you for using Wooventory in your store.
            </h3>
            <p>We hope you had a great experience. Please leave us with your feedback to serve best to you and others. Cheers! PS: you will also get 25% on your renewal payment as our thank you ;)</p>
            <p class="action-row">
                <button type="button" class="button button-primary" onclick="window.open('<?php echo $review_url; ?>', '_blank')">Review Now</button>
                <button type="button" class="button" onclick="rmsziHideReviewRequestNotice(this)">Remind Me Later</button>
                <span class="logo"><a target="_blank" href="https://wooventory.com">
                        <img src="<?php // echo esc_url(THWCFD_ASSETS_URL_ADMIN .'css/logo.svg');
        ?>" />
                    </a></span>

            </p>
        </div>
    <?php
}

    public static function dismiss_rmszi_review_request_notice()
    {
        $nonse = isset($_REQUEST['rmszi_security_review_notice']) ? $_REQUEST['rmszi_security_review_notice'] : false;
        if (!wp_verify_nonce($nonse, 'rmszi_review_request_notice') || !current_user_can('manage_woocommerce')) {
            die();
        }
        set_transient('rmszi_review_request_notice_dismissed', true, apply_filters('rmszi_dismissed_review_request_notice_lifespan', 1 * YEAR_IN_SECONDS));
    }

    public static function skip_rmszi_review_request_notice()
    {
        $nonse = isset($_REQUEST['rmszi_security_review_notice']) ? $_REQUEST['rmszi_security_review_notice'] : false;
        if (!wp_verify_nonce($nonse, 'rmszi_review_request_notice') || !current_user_can('manage_woocommerce')) {
            die();
        }
        set_transient('rmszi_skip_review_request_notice', true, apply_filters('rmszi_skip_review_request_notice_lifespan', 1 * DAY_IN_SECONDS));
    }

    /**
     * Plugins page
     * @return array
     */
    public static function pluginRowMeta($links, $file)
    {
        if ($file == RMS_BASENAME) {
            unset($links[2]);

            $customLinks = array(
                'documentation' => '<a href="' . RMS_DOCUMENTATION_URL . '" target="_blank">' . __('Documentation', 'rmsZI') . '</a>',
                'visit-plugin-site' => '<a href="' . RMS_PLUGIN_URL . '" target="_blank">' . __('Visit plugin site', 'rmsZI') . '</a>',
            );

            $links = array_merge($links, $customLinks);
        }

        return $links;
    }

    /**
     * Plugins page
     * @return array
     */
    public static function actionLinks($links)
    {
        $customLinks = array_merge(array('settings' => '<a href="' . admin_url('admin.php?page=' . RMS_MENU_SLUG) . '">' . __('Settings', 'rmsZI') . '</a>'), $links);

        return $customLinks;
    }

    /**
     * Admin footer
     * @return void
     */
    public static function adminFooter()
    {
        ?>
        <p><a href="https://wooventory.com/product/wooventory/" class="arg-review-link" target="_blank"><?php echo sprintf(__('If you like <strong> %s </strong> please leave us a &#9733;&#9733;&#9733;&#9733;&#9733; rating.', 'rmsZI'), RMS_PLUGIN_NAME); ?></a> <?php _e('Thank you.', 'rmsZI');?></p>
<?php
}
}
