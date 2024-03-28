<?php
/**
 * All code related to modifying the Webhook Payloads for CommerceBird API.
 *
 * @author   Fawad Tiemoerie <info@roadmapstudios.com>
 * @license  GNU General Public License v3.0
 * @link     https://commercebird.com
 * @since    2.0.0
 * @version  2.0.0
 * @category Webhook
 * @package  CommerceBird
 */

namespace CommerceBird;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Webhook_Modify {

	/**
	 * Webhook_Modify constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize all hooks.
	 */
	public function init_hooks() {
		add_filter( 'woocommerce_webhook_payload', array( $this, 'cm_modify_webhook_payload' ) );
	}

	/**
	 * Modify the Webhook Payload
	 * @param $payload
	 * @return mixed
	 * @since 2.0.0
	 */
	public function cm_modify_webhook_payload( $payload, $resource, $resource_id, $id ) {
			$webhook = wc_get_webhook( $id );
		if ( $webhook && $webhook->get_name() === 'CommerceBird Customers Update' ) {
			$new_payload = $this->cm_modify_customer_webhook_payload( $payload );
			return $new_payload;
		}
		if ( $webhook && $webhook->get_name() !== 'CommerceBird Orders' ) {
			$new_payload = $this->cm_modify_order_webhook_payload( $payload );
			return $new_payload;
		}
	}

	/**
	 * Modify the Customer Webhook Payload
	 * @param $payload
	 * @return mixed
	 * @since 2.0.0
	 * @throws \Exception
	 */
	public function cm_modify_customer_webhook_payload( $payload ) {
		$fd = fopen( __DIR__ . '/cm_modify_customer_webhook_payload.txt', 'w+' );

		$customer_id = $payload['arg'];
		if ( ! $customer_id ) {
			return $payload;
		}
		$endpoint = '/wc/v3/customers/' . $customer_id;
		$request  = new \WP_REST_Request( 'GET', $endpoint );
		$response = rest_do_request( $request );

		fwrite( $fd, PHP_EOL . 'response: ' . print_r( $response, true ) );
		fclose( $fd );

		$eo_account_id = (string) get_user_meta( $customer_id, 'eo_account_id', true );
		if ( ! empty( $eo_account_id ) ) {
			$payload['meta_data'][] = array(
				'key'   => 'eo_account_id',
				'value' => $eo_account_id,
			);
		}
		// attach all customer details to the payload
		$payload[''] = $response->get_data();

		return $payload;
	}

	/**
	 * Modify the Order Webhook Payload
	 * @param $payload
	 * @return mixed
	 * @since 2.0.0
	 * @throws \Exception
	 */
	public function cm_modify_order_webhook_payload( $payload ) {
		$eo_account_id = '';
		$customer_id   = (int) $payload['customer_id'];

		// All guest users will have the customer_id field set to 0
		if ( $customer_id > 0 ) {
			$eo_account_id = (string) get_user_meta( $customer_id, 'eo_account_id', true );
			if ( ! empty( $eo_account_id ) ) {
				$payload['meta_data'][] = array(
					'key'   => 'eo_account_id',
					'value' => $eo_account_id,
				);
			}
		}
		// Loop through line items in and add the eo_item_id to the line item
		foreach ( $payload['line_items'] as &$item ) {
			// Get the product ID associated with the line item
			$product_id   = $item['product_id'];
			$variation_id = $item['variation_id'];
			// Get the product meta value based on the product ID and meta key
			if ( $variation_id ) {
				$eo_item_id = get_post_meta( $variation_id, 'eo_item_id', true );
			} else {
				$eo_item_id = get_post_meta( $product_id, 'eo_item_id', true );
			}
			// Add the product meta to the line item
			if ( ! empty( $eo_item_id ) ) {
				$item['meta'][] = array(
					'key'   => 'eo_item_id',
					'value' => $eo_item_id,
				);
			}
		}

		return $payload;
	}
}

return new CM_Webhook_Modify();
