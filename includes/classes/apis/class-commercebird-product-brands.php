<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_REST_CommerceBird_Product_Brands_API_Controller extends WC_REST_CRUD_Controller {

	protected $namespace      = 'wc/v3';
	protected $rest_base      = 'product_brands';
	protected $empty_response = array(
		'status'  => 'error',
		'message' => 'Invalid request',
	);

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_brand' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_params(),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_brand' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_params(),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_brand' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}
	/**
	 * Get params of request.
	 *
	 * @return void
	 */
	public function get_params() {
		$params = array(
			'id'          => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'name'        => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'description' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'slug'        => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'parent'      => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'logo'        => array(
				'url'           => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'attachment_id' => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				),
			),
		);
		return $params;
	}

	/**
	 * Create brand within the product_brands taxonomy.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function create_brand( WP_REST_Request $request ) {
		$response = new WP_REST_Response();
		$data     = $request->get_params();
		if ( empty( $data['name'] ) ) {
			$response->set_data( $this->empty_response );
			$response->set_status( 400 );
			return $response;
		}
		$brand = wp_insert_term( $data['name'], 'product_brands', $data );
		// update the logo inside $data as term meta.
		if ( ! empty( $data['logo'] ) ) {
			update_term_meta( $brand['term_id'], 'logo', $data['logo'] );
		}
		if ( is_wp_error( $brand ) ) {
			$response->set_data( $brand->get_error_message() );
			$response->set_status( 500 );
			return $response;
		}
		// return the entire term object.
		$brand_object          = get_term_by( 'id', $brand['term_id'], 'product_brands' );
		$adjusted_brand_object = array(
			'id'          => $brand_object->term_id,
			'name'        => $brand_object->name,
			'slug'        => $brand_object->slug,
			'description' => $brand_object->description,
			'parent'      => $brand_object->parent,
		);
		$response->set_data( $adjusted_brand_object );
		$response->set_status( 200 );
		return $response;
	}

	/**
	 * Update brand within the product_brands taxonomy.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function update_brand( WP_REST_Request $request ) {
		$response = new WP_REST_Response();
		$data     = $request->get_params();
		if ( empty( $data['name'] ) ) {
			$response->set_data( $this->empty_response );
			$response->set_status( 400 );
			return $response;
		}
		$brand = wp_update_term( $data['id'], 'product_brands', $data );
		// update the logo inside $data as term meta.
		if ( ! empty( $data['logo'] ) ) {
			update_term_meta( $brand['term_id'], 'logo', $data['logo'] );
		}
		if ( is_wp_error( $brand ) ) {
			$response->set_data( $brand->get_error_message() );
			$response->set_status( 500 );
			return $response;
		}
		$brand_object          = get_term_by( 'id', $brand['term_id'], 'product_brands' );
		$adjusted_brand_object = array(
			'id'          => $brand_object->term_id,
			'name'        => $brand_object->name,
			'slug'        => $brand_object->slug,
			'description' => $brand_object->description,
			'parent'      => $brand_object->parent,
		);
		$response->set_data( $adjusted_brand_object );
		$response->set_status( 200 );
		return $response;
	}

	/**
	 * Delete brand within the product_brands taxonomy.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function delete_brand( WP_REST_Request $request ) {
		$response = new WP_REST_Response();
		$data     = $request->get_params();
		$term_id  = $data['id'];
		if ( empty( $data['id'] ) ) {
			$response->set_data( $this->empty_response );
			$response->set_status( 400 );
			return $response;
		}
		$brand = wp_delete_term( $data['id'], 'product_brands' );
		if ( is_wp_error( $brand ) ) {
			$response->set_data( $brand->get_error_message() );
			$response->set_status( 400 );
			return $response;
		}
		$delete_response = array(
			'id' => $term_id,
		);
		$response->set_data( $delete_response );
		$response->set_status( 200 );
		return $response;
	}
}
