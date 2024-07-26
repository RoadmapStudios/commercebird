<?php

namespace RMS\Admin\Actions\Sync;

defined( 'RMS_PLUGIN_NAME' ) || exit;

use ExecutecallClass;
use Classfunctions;
use WP_Error;

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

	/**
	 * Refresh the access token of Zoho CRM.
	 * @since 1.0.0
	 * @return void | \WP_Error
	 */
	public static function refresh_token() {
		// return if there is no access token
		if ( empty( get_option( 'zoho_crm_access_token' ) ) ) {
			return;
		}
		$app_name = 'zoho_crm';
		$zoho_refresh_token = get_option( 'zoho_crm_refresh_token' );
		$zoho_timestamp = get_option( 'zoho_crm_timestamp' );
		$current_time = strtotime( gmdate( 'Y-m-d H:i:s' ) );
		if ( $zoho_timestamp < $current_time ) {
			$handlefunction = new Classfunctions();
			$respo_at_js = $handlefunction->get_zoho_refresh_token( $zoho_refresh_token, $app_name );
			if ( is_wp_error( $respo_at_js ) ) {
				return new WP_Error( 403, 'Access denied!' );
			} else {
				update_option( 'zoho_crm_access_token', $respo_at_js['access_token'] );
				update_option( 'zoho_crm_timestamp', strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $respo_at_js['expires_in'] );
			}
		}
	}
}
