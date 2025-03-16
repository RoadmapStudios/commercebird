<?php

namespace CommerceBird;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CMBIRD_API_Handler_Zoho;

class CMBIRD_ZCRM_Modules {

	/**
	 * create API Get call to Zoho CRM to get all custom fields
	 *
	 * @param module $module - module name
	 * @return array | \WP_Error
	 */
	public function cmbird_get_module( $module ) {
		$zoho_crm_url = get_option( 'cmbird_zoho_crm_url' );
		$url = $zoho_crm_url . 'crm/v7/settings/fields?module=' . $module;
		$execute_curl_call_handle = new CMBIRD_API_Handler_Zoho();
		$json = $execute_curl_call_handle->execute_curl_call_get( $url );
		if ( is_wp_error( $json ) ) {
			return $json;
		} else {
			// Parse the response
			$parsed_fields = array();

			foreach ( $json->fields as $field ) {
				$parsed_fields[] = array(
					'id' => $field->id,
					'apiName' => $field->api_name,
					'visible' => (bool) $field->visible,
					'fieldLabel' => $field->field_label,
					'displayLabel' => $field->display_label,
					'customField' => (bool) $field->custom_field,
					'dataType' => $field->data_type,
				);
			}

			return $parsed_fields;
		}
	}

}