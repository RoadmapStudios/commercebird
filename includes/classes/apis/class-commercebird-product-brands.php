<?php
class WC_REST_CommerceBird_Product_Brands_API_Controller extends WC_REST_CRUD_Controller {

	protected $namespace      = 'wc/v2';
	protected $namespace2     = 'wc/v3';
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
					'permission_callback' => array( $this, 'check_permission_to_edit_posts' ),
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
					'permission_callback' => array( $this, 'check_permission_to_edit_posts' ),
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
					'permission_callback' => array( $this, 'check_permission_to_edit_posts' ),
					'args'                => $this->get_params(),
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
				'url'               => 'string',
				'attachment_id'     => 'integer',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
		return $params;
	}

	/**
	 * Check permission to edit posts
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function check_permission_to_edit_posts() {
		return current_user_can( 'edit_posts' );
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
		if ( is_wp_error( $brand ) ) {
			$response->set_data( $brand->get_error_message() );
			$response->set_status( 400 );
			return $response;
		}

		$response->set_data( 'created' );
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
		if ( is_wp_error( $brand ) ) {
			$response->set_data( $brand->get_error_message() );
			$response->set_status( 400 );
			return $response;
		}

		$response->set_data( 'updated' );
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

		$response->set_data( 'deleted' );
		$response->set_status( 200 );
		return $response;
	}
}
