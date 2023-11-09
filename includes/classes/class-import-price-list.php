<?php
/**
 * Class to import Pricelists from Zoho to WooCommerce using B2B for WooCommerce
 *
 * @package  WooZo Inventory
 */

class ImportPricelistClass
{

    public function __construct()
    {
        $this->config = [
            'ProductZI' => [
                'OID' => get_option('zoho_inventory_oid'),
                'APIURL' => get_option('zoho_inventory_url'),
            ],
        ];
    }

    /**
     * Function to get all Pricelists from Zoho
     *
     * @return mixed - json result
     */
    public function zi_get_all_pricelist()
    {
        // $fd = fopen(__DIR__ . '/zi_get_all_pricelist.txt', 'w+');

        $zoho_inventory_oid = $this->config['ProductZI']['OID'];
        $zoho_inventory_url = $this->config['ProductZI']['APIURL'];
        $url = $zoho_inventory_url . 'api/v1/pricebooks?organization_id=' . $zoho_inventory_oid;

        $executeCurlCallHandle = new ExecutecallClass();
        $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);
        // fwrite($fd, PHP_EOL . 'json: ' . print_r($json, true));
        $json2 = json_encode($json);
        // fclose($fd);

        return json_decode($json2, true);
    }

    /**
     * Function to get a specific chosen Pricelist from Zoho
     *
     * @param $pricebook_id - the ID of the chosen pricelist
     * @return mixed - json response of the pricelist
     */
    public function get_zi_pricelist($pricebook_id)
    {
        $zoho_inventory_oid = $this->config['ProductZI']['OID'];
        $zoho_inventory_url = $this->config['ProductZI']['APIURL'];
        $url = $zoho_inventory_url . 'api/v1/pricebooks/' . $pricebook_id . '?organization_id=' . $zoho_inventory_oid;

        $executeCurlCallHandle = new ExecutecallClass();
        $json = $executeCurlCallHandle->ExecuteCurlCallGet($url);
        $json2 = json_encode($json);

        $data = json_decode($json2, true);
        return $data;
    }

    /**
     * Function to apply the pricelist
     *
     */
    public function apply_zoho_pricelist($post)
    {
        // $fd = fopen(__DIR__ . '/apply_zoho_pricelist.txt', 'w+');

        $pricelist_id = $post['zoho_inventory_pricelist'];
        update_option('zoho_pricelist_id', $pricelist_id);
        $data = $this->get_zi_pricelist($pricelist_id);
        $pricebook_type = $data['pricebook']['pricebook_type'];

        // fwrite($fd, PHP_EOL . 'pricelist: ' . print_r($data, true));

        if (!empty($data)) {
            if ($pricebook_type == 'fixed_percentage') {
                $percentage = $data['pricebook']['percentage'];
                $is_increase = $data['pricebook']['is_increase'];
                $query = new WC_Product_Query(array(
                    'limit' => -1,
                    'return' => 'ids',
                ));
                $ids = $query->get_products();
                $percentage_order = '';
                if ($is_increase == true) {
                    $percentage_order = 'percentage_increase';
                } else {
                    $percentage_order = 'percentage_decrease';
                }
                $newpricelists['orderby'] = $percentage_order;
                foreach ($ids as $id) {
                    $zi_item_id = intval(get_post_meta($id, 'zi_item_id', true));
                    if ($zi_item_id > 0) {
                        $newpricelists['ids'][$zi_item_id] = $percentage;
                    }
                }
            } else {
                $newpricelists['orderby'] = 'fixed_price';
                foreach ($data['pricebook']['pricebook_items'] as $itemlist) {
                    if (is_array($itemlist['price_brackets']) && !empty($itemlist['price_brackets'])) {
                        $priceBracket = $itemlist['price_brackets'][0];
                        $newpricelists['ids'][$itemlist['item_id']] = array(
                            'start_quantity' => $priceBracket['start_quantity'],
                            'end_quantity' => $priceBracket['end_quantity'],
                            'pricebook_rate' => $priceBracket['pricebook_rate'],
                        );
                    } else {
                        $newpricelists['ids'][$itemlist['item_id']] = $itemlist['pricebook_rate'];
                    }
                }
            }
        }
        // fclose($fd); //end of logging
        return $newpricelists;
    }

    /**
     * Function executed when Save Pricelist Selection is clicked
     *
     * @param $post - the
     */
    public function save_pricelist( $post )
    {
        // $fd = fopen(__DIR__ . '/save_pricelist.txt', 'w+');

        $zoho_pricelists_ids = $this->apply_zoho_pricelist($post);

        // fwrite($fd, PHP_EOL . 'zoho_pricelists_ids: ' . print_r($zoho_pricelists_ids, true));

        global $wpdb;
        $array_keys = array_keys($zoho_pricelists_ids['ids']);
        foreach ($array_keys as $key) {
            $zoho_pricelists_price = $zoho_pricelists_ids['ids'][$key];

            $post_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'zi_item_id' AND meta_value = '%s' LIMIT 1", $key));
            $formatted_price = wc_get_price_decimal_separator();
            $zoho_pricelists_price2 = str_replace('.', $formatted_price, $zoho_pricelists_price);
            if ($post_id > 0) {
                $postmetaArr = get_post_meta($post_id, '_role_base_price', true);
                $rolewise_price_meta = array();

                $metavalue = array(
                    'discount_type' => $zoho_pricelists_ids['orderby'],
                );
                $metavalue['discount_type'] = $zoho_pricelists_ids['orderby'];

                // Check if the price value is an array
                if (is_array($zoho_pricelists_price2)) {
                    $metavalue['discount_value'] = $zoho_pricelists_price2['pricebook_rate'];
                    $metavalue['min_qty'] = $zoho_pricelists_price2['start_quantity'];
                    $metavalue['max_qty'] = $zoho_pricelists_price2['end_quantity'];
                } else {
                    $metavalue['discount_value'] = $zoho_pricelists_price2;
                    $metavalue['min_qty'] = '';
                    $metavalue['max_qty'] = '';
                }

                $metavalue['user_role'] = $post['wp_user_role'];

                $updated = false;

                if (is_array($postmetaArr) && !empty($postmetaArr)) {
                    foreach ($postmetaArr as &$postmeta) {
                        if ($postmeta['user_role'] === $post['wp_user_role']) {
                            $postmeta = $metavalue; // Update the existing meta value
                            $updated = true;
                            break;
                        }
                    }
                }

                if (!$updated) {
                    // Append the new meta value if not updated and array has content.
                    // Check if post meta array is empty.
                    if (!is_array($postmetaArr) || empty($postmetaArr)) {
                        $postmetaArr = array();
                    }
                    $postmetaArr[] = $metavalue;
                }
                update_post_meta($post_id, '_role_base_price', $postmetaArr);
            }
        }

        // fclose($fd); // end log
    }

    /**
     * Function to check if role based price exists
     */
    protected function zi_check_role_based_price_exists($postmetaArr, $role)
    {
        foreach ($postmetaArr as $postmeta) {
            if ($postmeta['user_role'] === $role) {
                return true;
            } else {
                return false;
            }
        }
    }
}
$importPricelist = new ImportPricelistClass();
