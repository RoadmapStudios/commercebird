<?php

namespace RMS\API;

use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Controller;

defined( 'RMS_PLUGIN_NAME' ) || exit();

class Exact extends WP_REST_Controller {

	protected $prefix = 'wc/v3';
	protected $rest_base = 'exact-webhooks';

	public function __construct() {

		register_rest_route(
			$this->prefix,
			'/' . $this->rest_base . '/process/',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'process_webhook' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);
	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function permission_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

	public function process_webhook( $request ) {
		$data = $request->get_json_params();

		// TODO: process the webhook data
		// use php switch case to handle different webhook types. e.g. Invoice, Item and StockPositions.
		// code starts here
		switch ( $data['type'] ) {
			case 'SalesInvoice':
				// process Invoice webhook. e.g. $data['data']['Description'] will give you the Invoice ID
				// match the Invoice Description with the Order ID in the database and update the order status
				// e.g. $order = wc_get_order( $order_id ); $order->update_status( 'completed' );
				$order_id = $data['data']['Description'];
				$order = wc_get_order( $order_id );
				if ( $order ) {
					// update the status if $data['data']['paymentReference'] is not null
					if ( ! empty( $data['data']['paymentReference'] ) ) {
						$order->update_status( 'completed' );
						$order->add_order_note( 'Order has been paid via Exact Online' );
					}
				}
				break;
			case 'Item':
				// process Item webhook. Find the product based on the $data['data']['Code'] which is the sku or post_id
				// update the product stock based on the $data['data']['Stock'] value
				// find the product_id based on the sku or post_id
				$product_id = wc_get_product_id_by_sku( $data['data']['Code'] );
				if ( ! $product_id ) {
					$product_id = $data['data']['Code'];
				}
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$product->set_stock_quantity( $data['data']['Stock'] );
					// update the product price if needed
					$product->set_price( $data['data']['Price'] );
					$product->save();
				} else {
					// if product not found, create a new product if the $data['data']['Webshop'] is set to true
					if ( ! $data['data']['IsWebshopItem'] ) {
						break;
					}
					$product = new WC_Product();
					$product->set_name( $data['data']['Description'] );
					$product->set_sku( $data['data']['Code'] );
					$product->set_stock_quantity( $data['data']['Stock'] );
					$product->set_price( $data['data']['Price'] );
					$product->set_regular_price( $data['data']['Price'] );
					$product->set_manage_stock( true );
					$product->set_stock_status( 'instock' );
					$product->set_status( 'publish' );
					$product->save();
				}
				break;
			case 'StockPositions':
				// process StockPositions webhook
				// get the "ItemId" from $data['data']['ItemId'] and find the product based on product meta key "eo_item_id"
				$item_id = $data['data']['ItemId'];
				// find the product id based on meta key "eo_item_id" which has the value of $item_id using WP query
				$args = array(
					'post_type' => 'product',
					'meta_query' => array(
						array(
							'key' => 'eo_item_id',
							'value' => $item_id,
						),
					),
				);
				$query = new WP_Query( $args );
				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
						$product_id = get_the_ID();
						$product = wc_get_product( $product_id );
						$product->set_stock_quantity( $data['data']['FreeStock'] );
						$product->save();
					}
				}
				break;
			default:
				// process default webhook
				break;
		}
		// code ends here

		return new WP_REST_Response( 'Webhook processed', 200 );
	}

}