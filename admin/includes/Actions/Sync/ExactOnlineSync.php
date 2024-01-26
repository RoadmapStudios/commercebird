<?php

namespace RMS\Admin\Actions\Sync;

defined( 'RMS_PLUGIN_NAME' ) || exit;

class ExactOnlineSync {


	/**
	 * Sync data from Exact Online.
	 *
	 * @param string $type product|customer
	 * @param string $data to sync from Exact Online
	 * @param bool $import import or update
	 * @return mixed
	 */
	public static function sync( string $type, string $data, bool $import = false ) {
		if ( empty( $type ) ) {
			return false;
		}
		$data = json_decode( $data, true );
		if ( empty( $data ) ) {
			return false;
		}
		foreach ( $data as $item ) {
			if ( $import ) {
				self::import( $type, $item );
			} else {
				self::update( $type, $item );
			}
		}
	}
	/**
	 * Import data from Exact Online.
	 *
	 * for product data will be like,
	 * {
	 * "Code":string,
	 * "Description": string,
	 * "ID": string,
	 * "IsSalesItem": bool,
	 * "PictureName": null|string,
	 * "PictureUrl": string,
	 * "StandardSalesPrice": float,
	 * "Stock": int
	 * },
	 *
	 * for Order data will be like,
	 * {
	 * "MainContact": null|string,
	 * "Email": null|string,
	 * "ID": string,
	 * "Name": string,
	 * "AddressLine1": null|string,
	 * "AddressLine2": null|string,
	 * "City": string,
	 * "Country": string,
	 * "Phone": null|string,
	 * "Postcode": string
	 * }
	 * @param string $type of provided data;
	 * @param array $data of import
	 * @return mixed
	 */
	public static function import( string $type, array $data ) {
		$endpoint = '';
		$payload  = array();
		switch ( $type ) {
			case 'product':
				$endpoint = '/wc/v3/products';
				$payload  = array(
					'name'           => $data['Description'],
					'sku'            => $data['Code'],
					'description'    => $data['Description'],
					'status'         => 'publish',
					'type'           => 'simple',
					'regular_price'  => (string) $data['StandardSalesPrice'],
					'stock_quantity' => (string) $data['Stock'],
					'images'         => array(
						array(
							'src' => $data['PictureUrl'],
						),
					),
					'meta_data'      => array(
						array(
							'key'   => 'eo_item_id',

							'value' => $data['ID'],
						),
					),

				);
				break;
			case 'customer':
				if ( empty( $data['Email'] ) ) {
					return;
				}
				$endpoint   = '/wc/v3/customers';
				$names      = explode( ' ', $data['Name'] );
				$first_name = $names[0] ?? '';
				$last_name  = $names[1] ?? '';
				$address    = array(
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'address_1'  => $data['AddressLine1'] ?? '',
					'address_2'  => $data['AddressLine2'] ?? '',
					'city'       => $data['City'],
					'country'    => $data['Country'],
					'postcode'   => $data['Postcode'],
					'phone'      => $data['Phone'] ?? '',
					'email'      => $data['Email'],
				);
				$payload    = array(
					'email'      => $data['Email'],
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'billing'    => $address,
					'shipping'   => $address,
					'meta_data'  => array(
						array(
							'key'   => 'eo_customer_id',
							'value' => $data['ID'],
						),
						array(
							'key'   => 'eo_account_id',
							'value' => $data['MainContact'] ?? '',
						),
					),
				);
				break;
			default:
				break;
		}

		if ( empty( $endpoint ) || empty( $payload ) ) {
			return false;
		}

		$request = new \WP_REST_Request( 'POST', $endpoint );
		$request->set_body_params( $payload );
		rest_do_request( $request );
	}
	/**
	 * Update data based on Exact Online.
	 * @param string $type of provided data
	 * @param array $data to match
	 * @return void
	 */
	public static function update( string $type, array $data ) {
		switch ( $type ) {
			case 'product':
				$wc_product_id = wc_get_product_id_by_sku( $data['Code'] );
				update_post_meta( $wc_product_id, 'eo_item_id', $data['ID'] );
				break;
			case 'customer':
				$user_id = get_user_by( 'email', $data['Email'] )->ID;
				update_user_meta( $user_id, 'eo_customer_id', $data['ID'] );
				if ( ! empty( $data['MainContact'] ) ) {
					update_user_meta( $user_id, 'eo_company_id', $data['MainContact'] );
				}
				break;
			default:
				break;
		}
	}
}
