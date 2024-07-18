<?php

namespace RMS\Admin\Actions\Sync;

defined( 'RMS_PLUGIN_NAME' ) || exit;

use ExecutecallClass;

class ZohoCRMSync {

	/* create API Get call to Zoho CRM to get all custom fields
	 *
	 * @param module $module - module name
	 * @return array | \WP_Error
	 */
	public static function get_custom_fields( $module ) {
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

			return $parsed_fields;
		}
	}
}
