<?php


class WCZohoInventory
{

    private static $options = array();
    private static $defaultOptions = array();

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
            load_plugin_textdomain('rmsZI', false, dirname(RMS_BASENAME) . '/languages');

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
        add_action('init', array( 'WCZohoInventory', 'init'));

        //Admin init
        add_action('admin_init', array( 'WCZohoInventory', 'adminInit'));

        //Admin notices
        add_action('admin_notices', array( 'WCZohoInventory', 'adminNotices'));
        add_action('wp_ajax_dismiss_rmszi_review_request_notice', array( 'WCZohoInventory', 'dismiss_rmszi_review_request_notice'));
        add_action('wp_ajax_skip_rmszi_review_request_notice', array( 'WCZohoInventory', 'skip_rmszi_review_request_notice'));

        
        //Plugins page
        add_filter('plugin_row_meta', array( 'WCZohoInventory', 'pluginRowMeta'), 10, 2);
        add_filter('plugin_action_links_' . RMS_BASENAME, array( 'WCZohoInventory', 'actionLinks'));

        //Admin page
        $page = filter_input(INPUT_GET, 'page');
        if (!empty($page) && $page == RMS_MENU_SLUG) {
            add_filter('admin_footer_text', array( 'WCZohoInventory', 'adminFooter'));
        }
    }

    /**
     * Init
     * @return void
     */
    public static function init()
    {
        //Load the plugin's translated strings
        load_plugin_textdomain('rmsZI', false, dirname(RMS_BASENAME) . '/languages');
    }

    /**
     * Admin init
     * @return void
     */
    public static function adminInit()
    {
        //Check plugin requirements
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
     * Admin menu
     * @return void
     */
    public static function adminMenu()
    {
        add_submenu_page(
            'woocommerce',
            RMS_PLUGIN_NAME,
            RMS_PLUGIN_NAME,
            'manage_woocommerce',
            RMS_MENU_SLUG,
            array( 'WCZohoInventory', 'adminOptions')
        );
    }

    /**
     * Enqueue scripts and styles for the admin
     */
    public static function enqueueScriptAdmin()
    {
        //Admin page
        $page = filter_input(INPUT_GET, 'page');
        if (empty($page) || $page !== RMS_MENU_SLUG) {
            return;
        }

        //Plugin admin styles
        wp_enqueue_style('rms-zi-styles-admin', RMS_DIR_URL . 'assets/css/styles-admin.css', array(), RMS_VERSION);
        wp_enqueue_style('rms-zi-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.2.0/css/font-awesome.min.css', array(), RMS_VERSION);

        //Plugin admin script
        wp_register_script('rms-zi-scripts-admin', RMS_DIR_URL . 'assets/js/scripts-admin.js', array('jquery'), RMS_VERSION, true);

        wp_register_script('sweatAlert', 'https://unpkg.com/sweetalert/dist/sweetalert.min.js', array('jquery'), RMS_VERSION, true);
        wp_enqueue_script('sweatAlert');

        wp_localize_script(
            'rms-zi-scripts-admin',
            'rmsZIJsVars',
            array(
                'ajaxURL' => admin_url('admin-ajax.php'),
            )
        );

        wp_enqueue_script('rms-zi-scripts-admin');
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

        $customer_review = get_option('zi_customer_review');
        if ($customer_review) {
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
        $review_url = "https://roadmapstudios.com/product/woocommerce-zoho-inventory";
        ?>
		<div id="rmszi_review_request_notice" class="notice notice-info is-dismissible  thpladmin-notice" data-nonce="<?php echo wp_create_nonce('rmszi_review_request_notice'); ?>" data-action="dismiss_rmszi_review_request_notice" style="display:none">
			<h3>
				Just wanted to say thank you for using Zoho Inventory plugin in your store.
			</h3>
			<p>We hope you had a great experience. Please leave us with your feedback to serve best to you and others. Cheers! PS: you will also get 25% on your renewal payment as our thank you ;)</p>
			<p class="action-row">
		        <button type="button" class="button button-primary" onclick="window.open('<?php echo $review_url; ?>', '_blank')">Review Now</button>
		        <button type="button" class="button" onclick="rmsziHideReviewRequestNotice(this)">Remind Me Later</button>
            	<span class="logo"><a target="_blank" href="https://roadmapstudios.com">
                	<img src="<?php // echo esc_url(THWCFD_ASSETS_URL_ADMIN .'css/logo.svg'); ?>" />
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
     * Set options
     */
    public static function setOptions($options, &$defaultOptions)
    {
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                self::setOptions($options[$key], $defaultOptions[$key]);
            } else {
                if (array_key_exists($key, $defaultOptions)) {
                    $defaultOptions[$key] = $value;
                }
            }
        }
    }

    /**
     * Admin options
     */
    public static function adminOptions()
    {
        global $wcam_lib;
        $data = filter_input_array(INPUT_POST);

        //Form submit
        if (!empty($data)) {

            $data = array_map('stripslashes_deep', $data);

            if (!empty($data['reset'])) {
                self::$options = self::$defaultOptions;
            } else {
                foreach ($data as $fieldName => $fieldValue) {
                    if ($fieldName == 'save' || $fieldName == 'reset') {
                        continue;
                    }

                    if (!array_key_exists($fieldName, self::$options)) {
                        continue;
                    }

                    if ($fieldName == 'steps') {
                        foreach ($fieldValue as $stepName => $stepValue) {
                            self::$options[$fieldName][$stepName]['text'] = $stepValue['text'];
                        }
                    } else {
                        self::$options[$fieldName] = $fieldValue;
                    }
                }
            }

            self::$options = apply_filters('rms-zi-update-options', self::$options);

            update_option('rms-zi-options', self::$options);
        }

        //Set options
        $options = self::$options;

        //Admin options
        $selectedTab = 'zoho';
        $sub_tab = 'ziconnect'; // Default tabe to be selected.
        $tab = filter_input(INPUT_GET, 'tab');
        $sub_tab = filter_input(INPUT_GET, 'stab');
        if (!empty($tab) && in_array($tab, array('general', 'steps', 'styles', 'zoho', 'custom-fields'))) {
            $selectedTab = $tab;
        }
        if ($wcam_lib->get_api_key_status()) {
            $customer_review = get_option('zi_customer_review');
            ?>
			<div class="rmsZI-wrapper">

				<div class="nav-tab-wrapper rmsZI-tab-wrapper">
					<a href="?page=<?php echo RMS_MENU_SLUG; ?>&tab=zoho&stab=ziconnect" class="nav-tab zi-plugin-head <?php echo $selectedTab == 'zoho' ? ' nav-tab-active' : ''; ?>"><?php _e('Zoho Inventory Settings', 'rmsZI');?></a>

				</div>
				<?php if ($selectedTab == 'zoho') {?>
					<div class="zi-menu-item">
						<ul class="zi-tab-list">
							<a href="?page=<?php echo RMS_MENU_SLUG; ?>&tab=zoho&stab=ziconnect" class="zi-tab <?php echo $sub_tab == 'ziconnect' ? ' zi-a-selected' : ''; ?>"><?php _e(' Connect', 'rmsZI');?></a>
							<a href="?page=<?php echo RMS_MENU_SLUG; ?>&tab=zoho&stab=zitax" class="zi-tab <?php echo $sub_tab == 'zitax' ? ' zi-a-selected' : ''; ?>"><?php _e(' Tax Mapping', 'rmsZI');?></a>
							<a href="?page=<?php echo RMS_MENU_SLUG; ?>&tab=zoho&stab=zisync" class="zi-tab <?php echo $sub_tab == 'zisync' ? ' zi-a-selected' : ''; ?>"><?php _e(' Products Sync', 'rmsZI');?></a>
							<a href="?page=<?php echo RMS_MENU_SLUG; ?>&tab=zoho&stab=zicron" class="zi-tab <?php echo $sub_tab == 'zicron' ? ' zi-a-selected' : ''; ?>"><?php _e(' Cron Configuration', 'rmsZI');?></a>
							<a href="?page=<?php echo RMS_MENU_SLUG; ?>&tab=zoho&stab=orders" class="zi-tab <?php echo $sub_tab == 'orders' ? ' zi-a-selected' : ''; ?>"><?php _e(' Orders Settings', 'rmsZI');?></a>
							<a href="?page=<?php echo RMS_MENU_SLUG; ?>&tab=zoho&stab=mapping" class="zi-tab <?php echo $sub_tab == 'mapping' ? ' zi-a-selected' : ''; ?>"><?php _e('Custom Fields', 'rmsZI');?></a>
							<a href="?page=<?php echo RMS_MENU_SLUG; ?>&tab=zoho&stab=pricelist" class="zi-tab <?php echo $sub_tab == 'pricelist' ? ' zi-a-selected' : ''; ?>"><?php _e('Price List', 'rmsZI');?></a>
							<ul>
					</div>
				<?php

                if ($sub_tab == 'ziconnect') {
                    include_once RMS_DIR_PATH . 'admin/template-parts/content-zoho.php';
                } elseif ('zitax' === $sub_tab) {
                    include_once RMS_DIR_PATH . 'admin/template-parts/content-zoho-tax.php';
                } elseif ('zisync' === $sub_tab) {
                    include_once RMS_DIR_PATH . 'admin/template-parts/content-zoho-sync.php';
                } elseif ('zicron' === $sub_tab) {
                    include_once RMS_DIR_PATH . 'admin/template-parts/content-zoho-cron.php';
                } elseif ('orders' === $sub_tab) {
                    include_once RMS_DIR_PATH . 'admin/template-parts/content-zoho-orders.php';
                } elseif ('mapping' === $sub_tab) {
                    include_once RMS_DIR_PATH . 'admin/template-parts/content-mapping.php';
                } elseif ('pricelist' === $sub_tab) {
                    include_once RMS_DIR_PATH . 'admin/template-parts/content-zoho-pricelist.php';
                } else {
                    include_once RMS_DIR_PATH . 'admin/template-parts/content-zoho.php';
                }
            }
            ?>

				<form method="post" class="rmsZI-form">

					<?php if ($selectedTab != 'zoho' && $selectedTab != 'zitax' && $selectedTab != 'zicron') {?>
						<input type="submit" name="save" class="button button-primary" value="<?php _e('Save Changes', 'rmsZI');?>">
						<input type="submit" name="reset" class="button" value="<?php _e('Reset All', 'rmsZI');?>">
					<?php }?>
				</form>

			</div>
		<?php
}
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
		<p><a href="https://roadmapstudios.com/product/woocommerce-zoho-inventory/" class="arg-review-link" target="_blank"><?php echo sprintf(__('If you like <strong> %s </strong> please leave us a &#9733;&#9733;&#9733;&#9733;&#9733; rating.', 'rmsZI'), RMS_PLUGIN_NAME); ?></a> <?php _e('Thank you.', 'rmsZI');?></p>
<?php
}
}
