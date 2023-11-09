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

add_action('woocommerce_thankyou', 'zi_find_order_id', 10, 1);
function zi_find_order_id($order_id)
{
    // check license
    global $wcam_lib;
    if (!$wcam_lib->get_api_key_status()) {
        return;
    }
?>
    <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'post',
                    data: {
                        'action': 'sync_zoho_order_thankyou',
                        'order_id': '<?php echo $order_id; ?>'
                    },
                    success: function(data) {

                    }
                });
            });
        })(jQuery);
    </script>
    <?php
}

add_action("wp_ajax_sync_zoho_order_thankyou", "sync_zoho_order_thankyou");
add_action("wp_ajax_nopriv_sync_zoho_order_thankyou", "sync_zoho_order_thankyou");
function sync_zoho_order_thankyou()
{
    // logging
    // $fd = fopen(__DIR__.'/front-order.txt', 'w+');
    $order_id = $_POST['order_id'];
    // fwrite($fd, PHP_EOL. 'order_id: '.$order_id);
    // fclose($fd);

    $sync_order = new OrderClass();
    $sync_order->zi_order_sync($order_id);
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
