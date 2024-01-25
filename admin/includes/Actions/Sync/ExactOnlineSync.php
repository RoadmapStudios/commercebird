<?php

namespace RMS\Admin\Actions\Sync;

defined( 'RMS_PLUGIN_NAME' ) || exit;

class ExactOnlineSync {
	public static function sync_products( string $products, bool $import = false ) {
		$products = json_decode( $products, true );

		foreach ( $products as $product ) {
			if ( $import ) {
				self::import_product( $product );
			} else {
				self::update_meta( $product );
			}
		}
	}

	public static function import_product( array $product ) {

			$request = new \WP_REST_Request( 'POST', '/wc/v3/products' );
			$request->set_body_params(
				array(
					'name'           => $product['Description'],
					'sku'            => $product['Code'],
					'description'    => $product['Description'],
					'status'         => 'publish',
					'type'           => 'simple',
					'regular_price'  => (string) $product['StandardSalesPrice'],
					'stock_quantity' => (string) $product['Stock'],
					'images'         => array(
						array(
							'src' => $product['PictureUrl'],
						),
					),
					'meta_data'      => array(
						array(
							'key'   => 'eo_item_id',

							'value' => $product['ID'],
						),
					),

				)
			);
			rest_do_request( $request );
	}

	public static function update_meta( array $product ) {
		$wc_product = wc_get_product_id_by_sku( $product['Code'] );
		update_meta( $wc_product, 'eo_item_id', $product['ID'] );
	}
}
