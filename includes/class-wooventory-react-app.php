<?php

/**
 * This Class Will Create Custom Rest Api Endpoints
 */

class Wp_React_App_By_wooventory
{
    public function construct(){
        add_action('rest_api_init', [$this, 'create_rest_routes']);
    }

    public function create_rest_routes(){
        register_rest_route('react/v1', '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => [$this, 'get_settings_permission']
        ]);

        register_rest_route('react/v1', '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'save_settings'],
            'permission_callback' => [$this, 'save_settings_permission']
        ]);
    }


    public function get_settings() {
        // $status = get_option( 'cors_status' );
        $status = false;
        $response = [
            'cors_status' => $status
        ];

        return rest_ensure_response( $response );
    }

    public function get_settings_permission() {
        return true;
    }

    public function save_settings( $req ) {
        $fp = fopen('.htaccess','a+');
        if($fp && strpos(file_get_contents(".htaccess"),"Access-Control-Allow-Origin") !== false){
            // fwrite($fp,'
            // <IfModule mod_headers.c>
            //     Header set Access-Control-Allow-Origin "*"
            // </IfModule>');
            // fclose($fp);
            echo 'Test <script> console.log("Debug"); </script>';
        }else{
            echo 'fine <script> console.log("work"); </script>';
        }
        return rest_ensure_response( 'success' );
    }

    public function save_settings_permission() {
        return true;
    }


}


new Wp_React_App_By_wooventory();

?>