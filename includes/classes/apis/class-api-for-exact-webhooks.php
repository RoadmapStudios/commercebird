<?php

namespace CommerceBird\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Controller;
use WP_REST_Request;
use ReflectionClass;
use WP_Query;
use WC_Product;

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
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
			)
		);
	}

	public function process_webhook( WP_REST_Request $request ) {
		// Access the protected body property using reflection
		$reflection = new ReflectionClass( $request );
		$property = $reflection->getProperty( 'body' );
		$property->setAccessible( true );
		$body = $property->getValue( $request );
		// Decode the JSON body
		$data = json_decode( $body, true );

		// use php switch case to handle different webhook types. e.g. Invoice, Item and StockPositions.
		// code starts here
		switch ( $data['type'] ) {
			case 'Items':
				// process Item webhook. Find the product based on the $data['Code'] which is the sku or post_id
				$product_id = wc_get_product_id_by_sku( $data['Code'] );
				if ( ! $product_id ) {
					$product_id = $data['Code'];
				}
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$product->set_stock_quantity( $data['Stock'] );
					// update the product price if needed
					$product->set_price( $data['StandardSalesPrice'] );
					$product->set_regular_price( $data['StandardSalesPrice'] );
					// add meta data cost_price
					$product->update_meta_data( '_cost_price', $data['CostPriceStandard'] );
					$product->save();
				} else {
					// if product not found, create a new product if the $data['Webshop'] is set to true
					if ( ! $data['IsWebshopItem'] ) {
						break;
					}
					$product = new WC_Product();
					$product->set_name( $data['Description'] );
					$product->set_sku( $data['Code'] );
					$product->set_price( $data['StandardSalesPrice'] );
					$product->set_regular_price( $data['StandardSalesPrice'] );
					if ( $data['Stock'] > 0 ) {
						$product->set_manage_stock( true );
						$product->set_stock_quantity( $data['Stock'] );
						$product->set_stock_status( 'instock' );
					}
					$product->set_status( 'draft' );
					$product->save();
				}
				break;
			case 'StockPositions':
				// process StockPositions webhook
				// get the "ItemId" from $data['ItemId'] and find the product based on product meta key "eo_item_id"
				$item_id = $data['ItemId'];
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

						if ( $product ) {
							$product->set_manage_stock( true );
							$product->set_stock_quantity( intval( $data['FreeStock'] ) );
							$product->save();
							// get new product instance
							$product = wc_get_product( $product_id );
							// get stock quantity of product, if its 0, set stock status to outofstock
							if ( $product->get_stock_quantity() <= 0 ) {
								$product->set_stock_status( 'outofstock' );
							} else {
								$stock_status = $product->backorders_allowed() ? 'onbackorder' : 'outofstock';
								$product->set_stock_status( $stock_status );
							}
							$product->save();
							// Clear cache
							wc_delete_product_transients( $product_id );
						}
					}
					wp_reset_postdata();
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
