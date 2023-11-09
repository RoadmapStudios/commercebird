<?php

/**
 * Here we load our custom Class for background queue
 *
 * @category Zoho_Integration
 * @package  WooZo_Inventory
 * @author   Roadmap Studios <info@roadmapstudios.com>
 * @license  GNU General Public License v3.0
 * @link     https://roadmapstudios.com
 */

if (!class_exists('WP_Zoho_Background_Process')) {
    class WP_Zoho_Background_Process
    {
        /**
         * @var string
         */
        protected $action = 'zoho_background_process';

        /**
         * Type of synchronization
         *
         * @var string item/contact
         */
        protected $sync_type = '';

        /**
         * Initiate new background process
         */
        public function __construct($type)
        {
            $this->sync_type = $type;
        }

        /**
         * Complete
         *
         * Override if applicable, but ensure that the below actions are
         * performed, or, call parent::complete().
         */
        protected function complete()
        {
            // Show notice to user or perform some other arbitrary task...
        }

        /**
         * Function to sync contact from woocommerce to zoho.
         *
         * @param mixed $user - user object to sync with zoho.
         * @return mixed
         */
        protected function syncContact($user)
        {
            $response_obj = (object) array(
                'code' => 400,
                'status' => false,
                'message' => 'SUCCESS',
            );
            if ($user && !$user->ID) {
                $response_obj->message = 'Contact id not found';
                return $response_obj;
            }
            $userid = $user->ID;
            $fname = get_user_meta($userid, 'first_name', true);
            $lname = get_user_meta($userid, 'last_name', true);
            $display_name = $user->display_name;
            $name = $fname . ' ' . $lname;
            $contact_name = ($name) ? $name : $display_name;
            $company_name = get_user_meta($userid, 'billing_company', true);
            $website = $user->user_url;
            $billing_address = get_user_meta($userid, 'billing_address_1', true);
            $billing_city = get_user_meta($userid, 'billing_city', true);
            $billing_state = get_user_meta($userid, 'billing_state', true);
            $billing_postcode = get_user_meta($userid, 'billing_postcode', true);
            $billing_country = get_user_meta($userid, 'billing_country', true);
            $shipping_address = get_user_meta($userid, 'shipping_address_1', true);
            $shipping_city = get_user_meta($userid, 'shipping_city', true);
            $shipping_state = get_user_meta($userid, 'shipping_state', true);
            $shipping_postcode = get_user_meta($userid, 'shipping_postcode', true);
            $shipping_country = get_user_meta($userid, 'shipping_country', true);
            $first_name = get_user_meta($userid, 'billing_first_name', true);
            $last_name = get_user_meta($userid, 'billing_last_name', true);
            $email = get_user_meta($userid, 'billing_email', true);
            $mobile = get_user_meta($userid, 'billing_phone', true);
            $is_primary_contact = 'true';
            $note = 'As a Customer';

            $user_details = get_user_meta($userid, 'zi_contact_id', true);

            if (!empty($user_details)) {
                $pdt2 = '"contact_name": "' . $contact_name . '","company_name": "' . $company_name . '","billing_address": { "address": "' . $billing_address . '","city": "' . $billing_city . '","state": "' . $billing_state . '","zip": "' . $billing_postcode . '","country": "' . $billing_country . '"},"shipping_address": { "address": "' . $shipping_address . '","city": "' . $shipping_city . '","state": "' . $shipping_state . '","zip": "' . $shipping_postcode . '","country": "' . $shipping_country . '"},"contact_persons": [{"first_name": "' . $first_name . '","last_name": "' . $last_name . '","phone": "' . $mobile . '","mobile": "' . $mobile . '","is_primary_contact": "' . $is_primary_contact . '"}],"notes": "' . $note . '"';
                $update_obj = $this->contact_zoho_user_infomation_update($user_details, $pdt2, $userid);

                $response_obj->message = $update_obj->message;
                $response_obj->code = $update_obj->code;
            } else {
                $handlefunction = new Classfunctions;
                $zoho_inventory_oid = get_option('zoho_inventory_oid');
                $zoho_inventory_url = get_option('zoho_inventory_url');
                $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
                $zoho_inventory_refresh_token = get_option('zoho_inventory_refresh_token');
                $zoho_inventory_timestamp = get_option('zoho_inventory_timestamp');
                $current_time = strtotime(date('Y-m-d H:i:s'));

                if ($zoho_inventory_timestamp < $current_time) {

                    $respoAtJs = $handlefunction->GetServiceZIRefreshToken($zoho_inventory_refresh_token);

                    $zoho_inventory_access_token = $respoAtJs['access_token'];
                    update_option('zoho_inventory_access_token', $respoAtJs['access_token']);
                    update_option('zoho_inventory_timestamp', strtotime(date('Y-m-d H:i:s')) + $respoAtJs['expires_in']);
                }

                $pdt1 = '"contact_name": "' . $contact_name . '","company_name": "' . $company_name . '","billing_address": { "address": "' . $billing_address . '","city": "' . $billing_city . '","state": "' . $billing_state . '","zip": "' . $billing_postcode . '","country": "' . $billing_country . '"},"shipping_address": { "address": "' . $shipping_address . '","city": "' . $shipping_city . '","state": "' . $shipping_state . '","zip": "' . $shipping_postcode . '","country": "' . $shipping_country . '"},"contact_persons": [{"salutation": "Mr","first_name": "' . $first_name . '","last_name": "' . $last_name . '","email": "' . $email . '","phone": "' . $mobile . '","mobile": "' . $mobile . '","is_primary_contact": "' . $is_primary_contact . '"}],"notes": "' . $note . '"';

                $data = array(
                    'JSONString' => '{' . $pdt1 . '}',
                    'organization_id' => $zoho_inventory_oid,
                );
                $url = $zoho_inventory_url . 'api/v1/contacts';
                $curl = curl_init($url);
                curl_setopt_array(
                    $curl,
                    array(
                        CURLOPT_POST => 1,
                        CURLOPT_POSTFIELDS => $data,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => array(
                            "Authorization: Bearer " . $zoho_inventory_access_token,
                        ),
                    )
                );

                $response = curl_exec($curl);

                $json = json_decode($response);

                $code = $json->code;
                $response_obj->code = $json->code;
                if ('0' == $code || 0 == $code) {
                    $this->update_customer_meta($json->contact, $userid);
                }
                $response_obj->message = $json->message;
            }
            return $response_obj;
        }

        /**
         * Function to update user metadata.
         *
         * @param mixed  $contact - zoho contact object to update user contact.
         * @param number $userid - woocommerce user id to update user meta.
         * @return void
         */
        protected function update_customer_meta($contact, $userid)
        {
            foreach ($contact as $key => $value) {
                if ($key == 'contact_id') {
                    $res['zi_contact_id'] = $value;
                }
                if ($key == 'primary_contact_id') {
                    $res['zi_primary_contact_id'] = $value;
                }
                if ($key == 'created_time') {
                    $res['zi_created_time'] = $value;
                }
                if ($key == 'last_modified_time') {
                    $res['zi_last_modified_time'] = $value;
                }
                if ($key == 'billing_address') {
                    $res['zi_billing_address_id'] = $value->address_id;
                }
                if ($key == 'shipping_address') {
                    $res['zi_shipping_address_id'] = $value->address_id;
                }
                if ($key == 'contact_persons') {
                    $res['zi_contact_persons_id'] = $value[0]->contact_person_id;
                }
            }

            foreach ($res as $key => $val) {
                update_user_meta($userid, $key, $val);
            }
        }

        /**
         * Zoho contact update
         *
         * @param number $customer_id - zoho customer id.
         * @param string $pdt4 - request data.
         * @param number $wc_userid - user id of woocommerce to update user.
         * @return string
         */
        protected function contact_zoho_user_infomation_update($customer_id, $pdt4, $wc_userid)
        {
            $handlefunction = new Classfunctions;

            $zoho_inventory_oid = get_option('zoho_inventory_oid');
            $zoho_inventory_url = get_option('zoho_inventory_url');
            $zoho_inventory_access_token = get_option('zoho_inventory_access_token');
            $zoho_inventory_refresh_token = get_option('zoho_inventory_refresh_token');
            $zoho_inventory_timestamp = get_option('zoho_inventory_timestamp');
            $current_time = strtotime(date('Y-m-d H:i:s'));

            if ($zoho_inventory_timestamp < $current_time) {

                $respoAtJs = $handlefunction->GetServiceZIRefreshToken($zoho_inventory_refresh_token);

                $zoho_inventory_access_token = $respoAtJs['access_token'];
                update_option('zoho_inventory_access_token', $respoAtJs['access_token']);
                update_option('zoho_inventory_timestamp', strtotime(date('Y-m-d H:i:s')) + $respoAtJs['expires_in']);
            }
            $url_u = $zoho_inventory_url . 'api/v1/contacts/' . $customer_id;

            $data_u = array(
                'JSONString' => '{' . $pdt4 . '}',
                'organization_id' => $zoho_inventory_oid,
            );

            $curl_u = curl_init($url_u);

            curl_setopt_array(
                $curl_u,
                array(
                    CURLOPT_POSTFIELDS => $data_u,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer " . $zoho_inventory_access_token,
                    ),
                )
            );

            curl_setopt($curl_u, CURLOPT_CUSTOMREQUEST, 'PUT');
            $putt = curl_exec($curl_u);

            $json_u = json_decode($putt);
            $code = $json_u->code;

            if ('0' == $code || 0 == $code) {
                $this->update_customer_meta($json_u->contact, $wc_userid);
            }

            return $json_u;
        }

        /**
         * Execute curl call and return response as json.
         *
         * @param string $url - URL to execute.
         * @return object
         */
        protected function execute_get_curl_call($url)
        {
            $curl = curl_init($url);
            curl_setopt_array(
                $curl,
                array(
                    CURLOPT_RETURNTRANSFER => true,
                )
            );

            $result = curl_exec($curl);
            return json_decode($result);
        }

    }}
