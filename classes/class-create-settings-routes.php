<?php
/**
 * This file will create Custom Rest API End Points.
 */
require __DIR__ . '../vendor/autoload.php';
use Automattic\WooCommerce\Client;

$woocommerce = new Client(
    'https://wooventory.com',
    'ck_b0305b88f6d5d26e6423c073f6f95de119cc9e55',
    'cs_609c13f281816b937499b2a0052e1145745acdc3',
    [
      'version' => 'wc/v3',
    ]
  );
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
        if($req["cors_status"] == true) {
            $fp = fopen('.htaccess','a+');
            if($fp){
                fwrite($fp,'
                <IfModule mod_headers.c>
                    Header set Access-Control-Allow-Origin "*"
                </IfModule>');
                fclose($fp);
            }
        }
        if($req["sub_id"] == true) {
            $sub_id = $req["sub_id"];
            $endpoint = 'subscriptions/' . $sub_id;
            try {
                // logging starts here
                $fd = fopen(__DIR__.'/get_subscription.txt','w+');

                $response = $woocommerce->get($endpoint);
                $result = $response->billing;
        
                fwrite($fd, PHP_EOL. print_r($response, true));
                fclose($fd);

                update_option('wooventory_sub_id', $sub_id);
                return $response;
            } catch (HttpClientException $e) {
                return $e->getMessage();
                // echo '<pre><code>' . print_r( $e->getRequest(), true ) . '</code><pre>'; // Last request data
        }

		update_option("enable_corse",$req["cors_status"]);
        return rest_ensure_response( 'success' );
    }

    public function save_settings_permission() {
        return true;
    }
}
new WP_React_Settings_Rest_Route();