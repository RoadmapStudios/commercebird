<?php

namespace CommerceBird;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CMBIRD_API_Handler_Zoho;

class CMBIRD_ZCRM_Contact {

    /**
     * create API Get call to Zoho CRM to get all contacts
     *
     * @return array | \WP_Error
     */
    public function cmbird_get_contacts() {
        $zoho_crm_url = get_option( 'cmbird_zoho_crm_url' );
        $url = $zoho_crm_url . 'crm/v2/Contacts';
        $execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
        $json = $execute_curl_call_handle->execute_curl_call_get( $url );
        if ( is_wp_error( $json ) ) {
            return $json;
        } else {
            // Parse the response
            $parsed_contacts = array();

            foreach ( $json->data as $contact ) {
                $parsed_contacts[] = array(
                    'id' => $contact->id,
                    'contact_name' => $contact->contact_name,
                    'account_name' => $contact->account_name,
                    'contact_type' => $contact->contact_type,
                    'phone' => $contact->phone,
                    'email' => $contact->email,
                    'website' => $contact->website,
                    'created_time' => $contact->created_time,
                    'modified_time' => $contact->modified_time,
                );
            }

            return $parsed_contacts;
        }
    }

    /**
     * create API Get call to Zoho CRM to get a contact
     *
     * @param contact_id $contact_id - contact id
     * @return array | \WP_Error
     */
	public function cmbird_get_contact( $contact_id ) {
		$zoho_crm_url = get_option( 'cmbird_zoho_crm_url' );
		$url = $zoho_crm_url . 'crm/v2/Contacts/' . $contact_id;
		$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call_handle->execute_curl_call_get( $url );
		if ( is_wp_error( $json ) ) {
			return $json;
		} else {
			// Parse the response
			$parsed_contact = array();

			$parsed_contact = array(
				'id' => $json->data->id,
				'contact_name' => $json->data->contact_name,
				'account_name' => $json->data->account_name,
				'contact_type' => $json->data->contact_type,
				'phone' => $json->data->phone,
				'email' => $json->data->email,
				'website' => $json->data->website,
				'created_time' => $json->data->created_time,
				'modified_time' => $json->data->modified_time,
			);

			return $parsed_contact;
		}
	}
}
