<?php
/**
 * This file will create Custom Rest API End Points.
 */
class WP_React_Settings_Rest_Route {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'create_rest_routes' ] );
    }

    public function create_rest_routes() {
        register_rest_route( 'react/v1', '/settings', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_settings' ],
            'permission_callback' => [ $this, 'get_settings_permission' ]
        ] );
        register_rest_route( 'react/v1', '/settings', [
            'methods' => 'POST',
            'callback' => [ $this, 'save_settings' ],
            'permission_callback' => [ $this, 'save_settings_permission' ]
        ] );
    }

    public function get_settings() {
        $response =[
			cors_status => get_option("enable_corse")
		];
        return rest_ensure_response( $response );
    }

    public function get_settings_permission() {
        return true;
    }

    public function save_settings( $req ) {
        //enable corse
        if($req["cors_status"] == true){
            $fp = fopen('.htaccess','a+');
            if($fp){
                fwrite($fp,'
                <IfModule mod_headers.c>
                    Header set Access-Control-Allow-Origin "*"
                </IfModule>');
                fclose($fp);
            }
        }

		update_option("enable_corse",$req["cors_status"]);
        return rest_ensure_response( 'success' );
    }

    public function save_settings_permission() {
        return true;
    }
}
new WP_React_Settings_Rest_Route();