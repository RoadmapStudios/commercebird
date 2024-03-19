<?php

/**
 * All user contact related functions.
 *
 * @package  WooZo Inventory
 * @category Zoho Integration
 * @author   Roadmap Studios
 * @link     https://commercebird.com
 */
class ContactClass {

	private array $config;
	public function __construct() {
		$config = array(

			'ContactZI' => array(
				'OID'    => get_option( 'zoho_inventory_oid' ),
				'APIURL' => get_option( 'zoho_inventory_url' ),

			),

		);

		$this->config = $config;
	}

	public function contact_create_function( $userid ) {
		if ( empty( $userid ) ) {
			return '';
		}
		// $fd         = fopen( __DIR__ . '/contact_create_function.txt', 'w+' );
		$user_order = wc_get_customer_last_order( $userid );

		if ( $user_order ) {
			// Billing Details
			$contact_name     = $user_order->get_billing_first_name() . ' ' . $user_order->get_billing_last_name();
			$company_name     = $user_order->get_billing_company();
			$billing_address  = $user_order->get_billing_address_1();
			$billing_address2 = $user_order->get_billing_address_2();
			$billing_city     = $user_order->get_billing_city();
			$billing_state    = $user_order->get_billing_state();
			$billing_postcode = $user_order->get_billing_postcode();
			$billing_country  = $user_order->get_billing_country();

			// Shipping Details
			$shipping_attention = $user_order->get_shipping_first_name() . ' ' . $user_order->get_shipping_last_name();
			$shipping_address   = $user_order->get_shipping_address_1();
			$shipping_address2  = $user_order->get_shipping_address_2();
			$shipping_city      = $user_order->get_shipping_city();
			$shipping_state     = $user_order->get_shipping_state();
			$shipping_postcode  = $user_order->get_shipping_postcode();
			$shipping_country   = $user_order->get_shipping_country();

			// Customer Details
			$first_name = $user_order->get_billing_first_name();
			$last_name  = $user_order->get_billing_last_name();
			$email      = $user_order->get_billing_email();
			$mobile     = $user_order->get_billing_phone();
		} else {
			// If no order found, fallback to user meta
			$contact_name     = get_user_meta( $userid, 'first_name', true ) . ' ' . get_user_meta( $userid, 'last_name', true );
			$company_name     = get_user_meta( $userid, 'billing_company', true );
			$billing_address  = get_user_meta( $userid, 'billing_address_1', true );
			$billing_address2 = get_user_meta( $userid, 'billing_address_2', true );
			$billing_city     = get_user_meta( $userid, 'billing_city', true );
			$billing_state    = get_user_meta( $userid, 'billing_state', true );
			$billing_postcode = get_user_meta( $userid, 'billing_postcode', true );
			$billing_country  = get_user_meta( $userid, 'billing_country', true );

			$shipping_attention = get_user_meta( $userid, 'shipping_first_name', true ) . ' ' . get_user_meta( $userid, 'shipping_last_name', true );
			$shipping_address   = get_user_meta( $userid, 'shipping_address_1', true );
			$shipping_address2  = get_user_meta( $userid, 'shipping_address_2', true );
			$shipping_city      = get_user_meta( $userid, 'shipping_city', true );
			$shipping_state     = get_user_meta( $userid, 'shipping_state', true );
			$shipping_postcode  = get_user_meta( $userid, 'shipping_postcode', true );
			$shipping_country   = get_user_meta( $userid, 'shipping_country', true );

			$first_name = get_user_meta( $userid, 'first_name', true );
			$last_name  = get_user_meta( $userid, 'last_name', true );
			$email      = get_user_meta( $userid, 'billing_email', true );
			$mobile     = get_user_meta( $userid, 'billing_phone', true );
		}

		$zi_customer_id = 0;

		// get vat_number
		$eu_vat = '';
		if ( class_exists( 'WC_EU_VAT_Number' ) ) {
			$eu_vat = get_user_meta( $userid, 'vat_number', true );
		}

		// If shipping not available asign billing as shipping.
		if ( empty( $shipping_address ) ) {
			$shipping_address = $billing_address;
		}
		if ( empty( $shipping_address2 ) ) {
			$shipping_address_2 = $billing_address2;
		}
		if ( empty( $shipping_postcode ) ) {
			$shipping_postcode = $billing_postcode;
		}
		if ( empty( $shipping_city ) ) {
			$shipping_city = $billing_city;
		}
		if ( empty( $shipping_country ) ) {
			$shipping_country = $billing_country;
		}
		if ( empty( $shipping_state ) ) {
			$shipping_state = $billing_state;
		}
		if ( empty( $shipping_attention ) ) {
			$shipping_attention = $contact_name;
		}

		//get last order
		$currency_id = get_user_meta( $userid, 'zi_currency_id', true );
		if ( empty( $currency_id ) && $userid ) {
			$user_order = wc_get_customer_last_order( $userid );
			if ( ! empty( $user_order ) ) {
				$user_currency         = $user_order->get_currency();
				$multi_currency_handle = new MulticurrencyClass();
				$multi_currency_handle->ZohoCurrencyData( $user_currency, $userid );
			}
		}

		// lets create the contact object
		if ( $company_name ) {
			$pdt1 = '"contact_name": "' . $company_name . '","contact_type": "customer","company_name": "' . $company_name . '"';
		} else {
			$pdt1 = '"contact_name": "' . $contact_name . '","contact_type": "customer"';
		}

		$pdt1 .= ',"email": "' . $email . '","mobile": "' . $mobile . '"';
		$pdt1 .= ',"currency_id": "' . $currency_id . '"';

		$pdt1 .= ',"billing_address": { "attention": "' . $contact_name . '","address": "' . $billing_address . '","street2": "' . $billing_address2 . '","city": "' . $billing_city . '","state": "' . $billing_state . '","zip": "' . $billing_postcode . '","country": "' . $billing_country . '"},"shipping_address": { "attention": "' . $shipping_attention . '","address": "' . $shipping_address . '","street2": "' . $shipping_address2 . '","city": "' . $shipping_city . '","state": "' . $shipping_state . '","zip": "' . $shipping_postcode . '","country": "' . $shipping_country . '"},"contact_persons": [{"first_name": "' . $first_name . '","last_name": "' . $last_name . '","email": "' . $email . '","phone": "' . $mobile . '","is_primary_contact": true}]';

		if ( $eu_vat > 0 ) {
			$pdt1 .= ',"vat_reg_no": "' . $eu_vat . '","country_code": "' . $billing_country . '"';
		}

		$zoho_inventory_oid = $this->config['ContactZI']['OID'];
		$zoho_inventory_url = $this->config['ContactZI']['APIURL'];

		$data = array(
			'JSONString'      => '{' . $pdt1 . '}',
			'organization_id' => $zoho_inventory_oid,
		);

		//logging
		// fwrite( $fd, PHP_EOL . 'Data Body: ' . print_r( $data, true ) );
		// end logging
		//fclose($fd);

		$url = $zoho_inventory_url . 'api/v1/contacts';

		$execute_curl_call_handle = new ExecutecallClass();
		$json                     = $execute_curl_call_handle->ExecuteCurlCallPost( $url, $data );

		$code = $json->code;

		if ( $code == '0' || $code == 0 ) {
			foreach ( $json->contact as $key => $value ) {

				if ( 'contact_id' === trim( $key ) ) {
					$res['zi_contact_id'] = $value;
				}
				if ( 'primary_contact_id' === trim( $key ) ) {
					$res['zi_primary_contact_id'] = $value;
				}
				if ( 'created_time' === trim( $key ) ) {
					$res['zi_created_time'] = $value;
				}
				if ( 'last_modified_time' === trim( $key ) ) {
					$res['zi_last_modified_time'] = $value;
				}
				if ( 'billing_address' === trim( $key ) ) {
					$res['zi_billing_address_id'] = $value->address_id;
				}
				if ( 'shipping_address' === trim( $key ) ) {
					$res['zi_shipping_address_id'] = $value->address_id;
				}
				if ( 'contact_persons' === trim( $key ) ) {
					$res['zi_contact_persons_id'] = $value;
				}
			}

			foreach ( $res as $key => $val ) {
				add_user_meta( $userid, $key, $val );
			}

			$zi_customer_id = get_user_meta( $userid, 'zi_contact_id', true );
		}

		return $zi_customer_id;
	}

	public function contact_update_function( $userid, $order_id = '' ) {
		//start logging
		// $fd=fopen(__DIR__.'/contact-update-sync.txt','w+');

		if ( $order_id ) {
			$order      = wc_get_order( $order_id );
			$order_data = $order->get_data();

			// BILLING INFORMATION:
			$fname        = $order_data['billing']['first_name'];
			$lname        = $order_data['billing']['last_name'];
			$name         = $fname . ' ' . $lname;
			$contact_name = $name;

			$company_name     = $order_data['billing']['company'];
			$billing_address  = $order_data['billing']['address_1'];
			$billing_address2 = $order_data['billing']['address_2'];
			$billing_city     = $order_data['billing']['city'];
			$billing_state    = $order_data['billing']['state'];
			$billing_postcode = $order_data['billing']['postcode'];
			$billing_country  = $order_data['billing']['country'];

			// SHIPPING INFORMATION:
			$shipping_first_name = $order_data['shipping']['first_name'];
			$shipping_last_name  = $order_data['shipping']['last_name'];
			$shipping_address    = $order_data['shipping']['address_1'];
			$shipping_address2   = $order_data['shipping']['address_2'];
			$shipping_city       = $order_data['shipping']['city'];
			$shipping_state      = $order_data['shipping']['state'];
			$shipping_postcode   = $order_data['shipping']['postcode'];
			$shipping_country    = $order_data['shipping']['country'];
		} else {
			$fname               = get_user_meta( $userid, 'billing_first_name', true );
			$lname               = get_user_meta( $userid, 'billing_last_name', true );
			$name                = $fname . ' ' . $lname;
			$contact_name        = $name;
			$company_name        = get_user_meta( $userid, 'billing_company', true );
			$billing_address     = get_user_meta( $userid, 'billing_address_1', true );
			$billing_address2    = get_user_meta( $userid, 'billing_address_2', true );
			$billing_city        = get_user_meta( $userid, 'billing_city', true );
			$billing_state       = get_user_meta( $userid, 'billing_state', true );
			$billing_postcode    = get_user_meta( $userid, 'billing_postcode', true );
			$billing_country     = get_user_meta( $userid, 'billing_country', true );
			$shipping_first_name = get_user_meta( $userid, 'shipping_first_name', true );
			$shipping_last_name  = get_user_meta( $userid, 'shipping_last_name', true );
			$shipping_attention  = $shipping_first_name . ' ' . $shipping_last_name;
			$shipping_address    = get_user_meta( $userid, 'shipping_address_1', true );
			$shipping_address2   = get_user_meta( $userid, 'shipping_address_2', true );
			$shipping_city       = get_user_meta( $userid, 'shipping_city', true );
			$shipping_state      = get_user_meta( $userid, 'shipping_state', true );
			$shipping_postcode   = get_user_meta( $userid, 'shipping_postcode', true );
			$shipping_country    = get_user_meta( $userid, 'shipping_country', true );
		}

		$zi_customer_id = get_user_meta( $userid, 'zi_contact_id', true );

		// get vat_number
		$eu_vat = '';
		if ( class_exists( 'WC_EU_VAT_Number' ) ) {
			$eu_vat = get_user_meta( $userid, 'vat_number', true );
		}

		// If shipping not available assign billing as shipping.
		if ( empty( $shipping_address ) ) {
			$shipping_address = $billing_address;
		}
		if ( empty( $shipping_address2 ) ) {
			$shipping_address_2 = $billing_address2;
		}
		if ( empty( $shipping_postcode ) ) {
			$shipping_postcode = $billing_postcode;
		}
		if ( empty( $shipping_city ) ) {
			$shipping_city = $billing_city;
		}
		if ( empty( $shipping_country ) ) {
			$shipping_country = $billing_country;
		}
		if ( empty( $shipping_state ) ) {
			$shipping_state = $billing_state;
		}
		if ( empty( $shipping_attention ) ) {
			$shipping_attention = $contact_name;
		}

		// lets update the contact object
		if ( ! empty( $company_name ) ) {
			$pdt2 = '"contact_name": "' . $company_name . '","contact_type": "customer","company_name": "' . $company_name . '"';
		} else {
			$pdt2 = '"contact_name": "' . $contact_name . '","contact_type": "customer"';
		}

		$pdt2 .= ',"billing_address": { "attention": "' . $contact_name . '","address": "' . $billing_address . '","street2": "' . $billing_address2 . '","city": "' . $billing_city . '","state": "' . $billing_state . '","zip": "' . $billing_postcode . '","country": "' . $billing_country . '"},"shipping_address": { "attention": "' . $shipping_attention . '","address": "' . $shipping_address . '","street2": "' . $shipping_address2 . '","city": "' . $shipping_city . '","state": "' . $shipping_state . '","zip": "' . $shipping_postcode . '","country": "' . $shipping_country . '"}';

		if ( $eu_vat > 0 ) {
			$pdt2 .= ',"vat_reg_no": "' . $eu_vat . '","country_code": "' . $billing_country . '"';
		}

		$zoho_inventory_oid = $this->config['ContactZI']['OID'];
		$zoho_inventory_url = $this->config['ContactZI']['APIURL'];

		$data = array(
			'JSONString'      => '{' . $pdt2 . '}',
			'organization_id' => $zoho_inventory_oid,
		);
		// fwrite($fd,PHP_EOL.'data: '.print_r($data, true));
		$url = $zoho_inventory_url . 'api/v1/contacts/' . $zi_customer_id;
		// fwrite($fd,PHP_EOL.'URL: '. $url);
		$execute_curl_call_handle = new ExecutecallClass();
		$json                     = $execute_curl_call_handle->ExecuteCurlCallPut( $url, $data );
		// fwrite($fd, PHP_EOL.'Response log : '.print_r($json, true));
		$code   = $json->code;
		$errmsg = $json->message;

		//logging
		// fwrite($fd, PHP_EOL.'Error log : '.print_r($errmsg, true));
		// end logging
		// fclose($fd);

		if ( $code == '0' || $code == 0 ) {
			/* Update Create a contactperson within that contact if email_id do not match */
			$res_msg = $this->update_contact_email_address( $userid );
		}

		return $res_msg;
	}

	public function update_contact_email_address( $userid ) {
		// Re-add contact email address after updating contact
		$zi_customer_id      = get_user_meta( $userid, 'zi_contact_id', true );
		$fname               = get_user_meta( $userid, 'billing_first_name', true );
		$lname               = get_user_meta( $userid, 'billing_last_name', true );
		$contact_name        = $fname . ' ' . $lname;
		$email               = get_user_meta( $userid, 'billing_email', true );
		$mobile              = get_user_meta( $userid, 'billing_phone', true );
		$company_name        = get_user_meta( $userid, 'billing_company', true );
		$billing_address     = get_user_meta( $userid, 'billing_address_1', true );
		$billing_address2    = get_user_meta( $userid, 'billing_address_2', true );
		$billing_city        = get_user_meta( $userid, 'billing_city', true );
		$billing_state       = get_user_meta( $userid, 'billing_state', true );
		$billing_postcode    = get_user_meta( $userid, 'billing_postcode', true );
		$billing_country     = get_user_meta( $userid, 'billing_country', true );
		$shipping_first_name = get_user_meta( $userid, 'shipping_first_name', true );
		$shipping_last_name  = get_user_meta( $userid, 'shipping_last_name', true );
		$shipping_attention  = $shipping_first_name . ' ' . $shipping_last_name;
		$shipping_address    = get_user_meta( $userid, 'shipping_address_1', true );
		$shipping_address2   = get_user_meta( $userid, 'shipping_address_2', true );
		$shipping_city       = get_user_meta( $userid, 'shipping_city', true );
		$shipping_state      = get_user_meta( $userid, 'shipping_state', true );
		$shipping_postcode   = get_user_meta( $userid, 'shipping_postcode', true );
		$shipping_country    = get_user_meta( $userid, 'shipping_country', true );

		// If shipping not available asign billing as shipping.
		if ( empty( $shipping_address ) ) {
			$shipping_address = $billing_address;
		}
		if ( empty( $shipping_address2 ) ) {
			$shipping_address_2 = $billing_address2;
		}
		if ( empty( $shipping_postcode ) ) {
			$shipping_postcode = $billing_postcode;
		}
		if ( empty( $shipping_city ) ) {
			$shipping_city = $billing_city;
		}
		if ( empty( $shipping_country ) ) {
			$shipping_country = $billing_country;
		}
		if ( empty( $shipping_state ) ) {
			$shipping_state = $billing_state;
		}
		if ( empty( $shipping_attention ) ) {
			$shipping_attention = $contact_name;
		}

		/* Create json form again to update whole data */
		if ( ! empty( $company_name ) ) {
			$json_form = '"contact_name": "' . $company_name . '","contact_type": "customer","company_name": "' . $company_name . '"';
		} else {
			$json_form = '"contact_name": "' . $contact_name . '","contact_type": "customer"';
		}
		$json_form .= ',"email": "' . $email . '","mobile": "' . $mobile . '"';
		$json_form .= ',"billing_address": { "attention": "' . $contact_name . '","address": "' . $billing_address . '","street2": "' . $billing_address2 . '","city": "' . $billing_city . '","state": "' . $billing_state . '","zip": "' . $billing_postcode . '","country": "' . $billing_country . '"},"shipping_address": { "attention": "' . $shipping_attention . '","address": "' . $shipping_address . '","street2": "' . $shipping_address2 . '","city": "' . $shipping_city . '","state": "' . $shipping_state . '","zip": "' . $shipping_postcode . '","country": "' . $shipping_country . '"}';

		if ( get_user_meta( $userid, 'vat_number', true ) > 0 ) {
			$json_form .= ',"vat_reg_no": "' . get_user_meta( $userid, 'vat_number', true ) . '","country_code": "' . $billing_country . '"';
		}

		$zoho_inventory_oid = $this->config['ContactZI']['OID'];
		$zoho_inventory_url = $this->config['ContactZI']['APIURL'];

		$jsonData = array(
			'JSONString'      => '{' . $json_form . '}',
			'organization_id' => $zoho_inventory_oid,
		);

		$execute_curl_call_handle = new ExecutecallClass();
		$update_url               = $zoho_inventory_url . 'api/v1/contacts/' . $zi_customer_id;
		$json                     = $execute_curl_call_handle->ExecuteCurlCallPut( $update_url, $jsonData );
		$res_msg                  = $json->message;
		$code                     = $json->code;

		if ( $code == '0' || $code == 0 ) {
			foreach ( $json->contact as $key => $value ) {
				if ( 'last_modified_time' == trim( $key ) ) {
					update_user_meta( $userid, 'zi_last_modified_time', trim( $value ) );
				}
				if ( 'billing_address' == trim( $key ) ) {
					update_user_meta( $userid, 'zi_billing_address_id', trim( $value->address_id ) );
				}
				if ( 'shipping_address' == trim( $key ) ) {
					update_user_meta( $userid, 'zi_shipping_address_id', trim( $value->address_id ) );
				}
				if ( 'contact_persons' == trim( $key ) ) {
					if ( is_object( $value ) ) {
						update_user_meta( $userid, 'zi_contact_persons_id', trim( $value->contact_person_id ) );
					}
				}
			}
		}
		return $res_msg;
	}

	public function create_contact_person( $userid ) {
		// $fd=fopen(__DIR__.'/create_contact_person.txt','w+');

		$zi_customer_id = get_user_meta( $userid, 'zi_contact_id', true );
		$fname          = get_user_meta( $userid, 'first_name', true );
		$lname          = get_user_meta( $userid, 'last_name', true );
		$email          = get_user_meta( $userid, 'billing_email', true );
		$mobile         = get_user_meta( $userid, 'billing_phone', true );

		$zoho_inventory_oid = $this->config['ContactZI']['OID'];
		$zoho_inventory_url = $this->config['ContactZI']['APIURL'];

		$contact_person_data = '"contact_id": "' . $zi_customer_id . '","first_name": "' . $fname . '","last_name":"' . $lname . '","email": "' . $email . '","phone": "' . $mobile . '","mobile": "' . $mobile . '"';
		$data                = array(
			'JSONString'      => '{' . $contact_person_data . '}',
			'organization_id' => $zoho_inventory_oid,
		);

		$url = $zoho_inventory_url . 'api/v1/contacts/contactpersons';

		$execute_curl_call_handle = new ExecutecallClass();
		$json                     = $execute_curl_call_handle->ExecuteCurlCallPost( $url, $data );

		// fwrite($fd, PHP_EOL.'JSON : '.print_r($json, true));

		// fclose($fd);
		$code   = $json->code;
		$errmsg = $json->message;
		if ( $code == 0 || $code == '0' ) {
			return $json->contact_id;
		} else {
			return $errmsg;
		}
	}

	public function update_contact_person( $userid, $contact_person_id ) {
		$zi_customer_id = get_user_meta( $userid, 'zi_contact_id', true );
		$fname          = get_user_meta( $userid, 'first_name', true );
		$lname          = get_user_meta( $userid, 'last_name', true );
		$email          = get_user_meta( $userid, 'billing_email', true );
		$mobile         = get_user_meta( $userid, 'billing_phone', true );

		$zoho_inventory_oid = $this->config['ContactZI']['OID'];
		$zoho_inventory_url = $this->config['ContactZI']['APIURL'];

		$contact_person_data = '"contact_id": ' . $zi_customer_id . ',"first_name": "' . $fname . '","last_name":"' . $lname . '","email": "' . $email . '","phone": "' . $mobile . '","mobile": "' . $mobile . '"';
		$data                = array(
			'JSONString'      => '{' . $contact_person_data . '}',
			'organization_id' => $zoho_inventory_oid,
		);

		$url = $zoho_inventory_url . 'api/v1/contacts/contactpersons/' . $contact_person_id;

		$execute_curl_call_handle = new ExecutecallClass();
		$json                     = $execute_curl_call_handle->ExecuteCurlCallPut( $url, $data );

		$code   = $json->code;
		$errmsg = $json->message;
		return $errmsg;
	}

	public function get_zoho_contacts( $page ) {
		$args = func_get_args();
		if ( ! empty( $args ) ) {
			$page = $args[0];
		} else {
			return;
		}
		/* Get Url and org id */
		$zoho_inventory_oid = $this->config['ContactZI']['OID'];
		$zoho_inventory_url = $this->config['ContactZI']['APIURL'];

		/* Get call url */
		$url                      = $zoho_inventory_url . 'api/v1/contacts?organization_id=' . $zoho_inventory_oid . '&filter_by=Status.Active&per_page=100&page=' . $page;
		$execute_curl_call_handle = new ExecutecallClass();
		$json                     = $execute_curl_call_handle->ExecuteCurlCallGet( $url );

		if ( isset( $json->contacts ) ) {
			foreach ( $json->contacts as $contacts ) {
				$contact_type   = trim( $contacts->contact_type );
				$zohocontact_id = $contacts->contact_id;
				$phone          = $contacts->phone;
				$company_name   = $contacts->company_name;
				$first_name     = $contacts->first_name;
				$last_name      = $contacts->last_name;
				$email          = trim( $contacts->email );
				//check if user type is customer
				if ( ! empty( $email ) && ! empty( $first_name ) && ! empty( $last_name ) ) {
					if ( $contact_type == 'customer' && ! email_exists( $email ) ) {
						/* Create Wp User */
						$userData = array(
							'user_login'   => $first_name . '.' . $last_name,
							'display_name' => $first_name . ' ' . $last_name,
							'user_email'   => $email,
							'first_name'   => $first_name,
							'last_name'    => $last_name,
							'role'         => 'customer',
						);
						$user_id  = wp_insert_user( $userData );
						if ( ! is_wp_error( $user_id ) ) {
							update_user_meta( $user_id, 'zi_contact_id', $zohocontact_id );
							update_user_meta( $user_id, 'billing_company', $company_name );
							update_user_meta( $user_id, 'billing_phone', $phone );
						} else {
							echo $user_id->get_error_message();
						}
					} elseif ( email_exists( $email ) ) {
						/* Update Wp User if already exist */
						$user_data = get_user_by( 'email', $email );
						$user_id   = $user_data->ID;
						$is_err    = wp_update_user(
							array(
								'ID'           => $user_id,
								'display_name' => $first_name . ' ' . $last_name,
								'user_email'   => $email,
								'first_name'   => $first_name,
								'last_name'    => $last_name,
							)
						);
						if ( ! is_wp_error( $is_err ) ) {
							update_user_meta( $user_id, 'zi_contact_id', $zohocontact_id );
							update_user_meta( $user_id, 'billing_company', $company_name );
							update_user_meta( $user_id, 'billing_phone', $phone );
						} else {
							echo $is_err->get_error_message();
						}
					}
				}
			}
			if ( $json->page_context['has_more_page'] === true ) {
				++$page;
				$data_arr          = (object) array();
				$data_arr->page    = $page;
				$existing_schedule = as_has_scheduled_action( 'sync_zi_import_contacts', array( $data_arr ) );
				if ( ! $existing_schedule ) {
					as_schedule_single_action( time(), 'sync_zi_import_contacts', array( $data_arr ) );
				}
			}
		}
	}
}
