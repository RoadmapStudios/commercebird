<?php

namespace CommerceBird;

use ExecutecallClass;

class ZCRM_Custom_Fields {

	/**
	 * create API Get call to Zoho CRM to get all custom fields
	 *
	 * @TODO: This function is not used anywhere in the plugin. It should be used as example.
	 * @param module $module - module name
	 * @return array | \WP_Error
	 */
	public function get_custom_fields( $module ) {
		$fd = fopen( __DIR__ . '/get_custom_fields.txt', 'w+' );
		$zoho_crm_url = get_option( 'zoho_crm_url' );
		$url = $zoho_crm_url . 'crm/v6/settings/fields?module=' . $module;
		$execute_curl_call_handle = new ExecutecallClass();
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

			// Optionally log the parsed fields to the file for debugging
			fwrite( $fd, print_r( $parsed_fields, true ) );
			fclose( $fd );

			return $parsed_fields;
		}
	}

}