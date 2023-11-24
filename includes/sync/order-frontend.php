<?php

/**
 * All Frontend Order sync related functions.
 *
 * @package  WooZo Inventory
 * @category Zoho Integration
 * @author   Roadmap Studios
 * @link     https://wooventory.com
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sync the order from frontend to Zoho Inventory
 */
add_action('wp_enqueue_scripts', 'zi_sync_frontend_order');
function zi_sync_frontend_order() {

    // Check if we are on the "Order Received" page
    if (is_wc_endpoint_url('order-received')) {
        // Output JavaScript to delay the execution
        wp_enqueue_script('zoho-frontend-ajax', RMS_DIR_URL . 'admin/js/zoho_frontend_order.js', array('jquery'), RMS_VERSION, true);
        wp_localize_script('zoho-frontend-ajax', 'frontendajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }
}


/**
 * Function to map customer on checkout before placing order
 */
add_action('template_redirect', 'zoho_contact_check');
function zoho_contact_check()
{

    if (is_user_logged_in() && is_checkout()) {

        $current_user = wp_get_current_user();
        $customer_id = intval(get_user_meta($current_user->ID, 'zi_contact_id', true));
        $customer_email = $current_user->user_email;
        $zoho_inventory_oid = get_option('zoho_inventory_oid');
        $zoho_inventory_url = get_option('zoho_inventory_url');

        if ($customer_id == 0) {
            $url = $zoho_inventory_url . 'api/v1/contacts?organization_id=' . $zoho_inventory_oid . '&email=' . $customer_email;

            $executeCurlCallHandle = new ExecutecallClass();
            $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);
            //echo '<pre>'; print_r($json);
            $code = $json->code;
            if ($code == 0) {
                if (count($json->contacts) > 2) {
                    foreach ($json->contacts as $val) {
                        if ($val->contact_id > 0) {
                            update_user_meta($current_user->ID, 'zi_contact_id', $val->contact_id);
                        }
                    }
                }
            }
        }
    }
}

/**
 * Contact add in Zoho from WC Checkout page
 */
add_action('wp_footer', 'zoho_post_contact_js');
function zoho_post_contact_js()
{

    // Only on Checkout
    if (is_checkout() && !is_wc_endpoint_url()) :

        $multicurrency_support = get_option('zoho_enable_multicurrency_status');

        if ($multicurrency_support != 'true') {
            return;
        }

        if (!is_user_logged_in()) {
            echo '<style type="text/css">
	        form.checkout {
	            display: none;
	        }
			body form.woocommerce-checkout {
				display: none;
			}
			.woocommerce-form-coupon-toggle{
				display: none;
			}
	        </style>';
        }
    ?>
        <script type="text/javascript" defer="defer">
            //fetch currency code
            jQuery(document).ready(function() {
                var currency_code = jQuery('.currency_code').first().text();
                jQuery.ajax({
                    type: 'POST',
                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                    async: false,
                    data: {
                        action: 'get_currency_code',
                        currency_code: currency_code,
                    },
                    success: function(response) {},
                });
            });
        </script>
<?php
    endif;
}

/**
 * Receive currency_code from checkout
 */
add_action('wp_ajax_get_currency_code', 'get_currency_code');
add_action('wp_ajax_nopriv_get_currency_code', 'get_currency_code');

function get_currency_code()
{
    $userid = get_current_user_id();
    $user_currency = $_POST['currency_code'];

    if ($userid > 0) {
        $multiCurrencyHandle = new MulticurrencyClass();
        $currency_id = $multiCurrencyHandle->ZohoCurrencyData($user_currency, $userid);
    }
}
