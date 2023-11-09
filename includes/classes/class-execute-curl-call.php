<?php

/**
 * All Execute Call Class related functions.
 *
 * @package  Inventory
 */

class ExecutecallClass
{
	/**
	 * @var array|array[]
	 */
	private array $config;

	public function __construct()
    {
        $this->config = [
            'ExecutecallZI' => [
                'OID' => get_option('zoho_inventory_oid'),
                'ATOKEN' => get_option('zoho_inventory_access_token'),
                'RTOKEN' => get_option('zoho_inventory_refresh_token'),
                'EXPIRESTIME' => get_option('zoho_inventory_timestamp'),
            ],
        ];
    }    

    // Get Call Zoho
    public function ExecuteCurlCallGet($url)
    {
        // Sleep for .5 sec for each api calls
        usleep(500000);
        $handlefunction = new Classfunctions;

        $zoho_inventory_access_token = $this->config['ExecutecallZI']['ATOKEN'];
        $zoho_inventory_refresh_token = $this->config['ExecutecallZI']['RTOKEN'];
        $zoho_inventory_timestamp = $this->config['ExecutecallZI']['EXPIRESTIME'];

        $current_time = strtotime(date('Y-m-d H:i:s'));

        if ($zoho_inventory_timestamp < $current_time) {

            $respoAtJs = $handlefunction->GetServiceZIRefreshToken($zoho_inventory_refresh_token);
			if (empty($respoAtJs)) {
				return new WP_Error(403, "Access denied!");
			}
            $zoho_inventory_access_token = $respoAtJs['access_token'];
            update_option('zoho_inventory_access_token', $respoAtJs['access_token']);
            update_option('zoho_inventory_timestamp', strtotime(date('Y-m-d H:i:s')) + $respoAtJs['expires_in']);

        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $zoho_inventory_access_token,
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

	    return json_decode($response);

    }

    // Post Call Zoho

    public function ExecuteCurlCallPost($url, $data)
    {

        $handlefunction = new Classfunctions;

        $zoho_inventory_access_token = $this->config['ExecutecallZI']['ATOKEN'];
        $zoho_inventory_refresh_token = $this->config['ExecutecallZI']['RTOKEN'];
        $zoho_inventory_timestamp = $this->config['ExecutecallZI']['EXPIRESTIME'];

        $current_time = strtotime(date('Y-m-d H:i:s'));

        if ($zoho_inventory_timestamp < $current_time) {

            $respoAtJs = $handlefunction->GetServiceZIRefreshToken($zoho_inventory_refresh_token);

            $zoho_inventory_access_token = $respoAtJs['access_token'];
            update_option('zoho_inventory_access_token', $respoAtJs['access_token']);
            update_option('zoho_inventory_timestamp', strtotime(date('Y-m-d H:i:s')) + $respoAtJs['expires_in']);

        }

        $curl = curl_init($url);
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer " . $zoho_inventory_access_token,
                ),
            )
        );

        $response = curl_exec($curl);
        curl_close($curl);

        $json = json_decode($response);

        return $json;
    }

    // Put Call Zoho

    public function ExecuteCurlCallPut($url, $data)
    {

        $handlefunction = new Classfunctions;

        $zoho_inventory_access_token = $this->config['ExecutecallZI']['ATOKEN'];
        $zoho_inventory_refresh_token = $this->config['ExecutecallZI']['RTOKEN'];
        $zoho_inventory_timestamp = $this->config['ExecutecallZI']['EXPIRESTIME'];

        $current_time = strtotime(date('Y-m-d H:i:s'));

        if ($zoho_inventory_timestamp < $current_time) {

            $respoAtJs = $handlefunction->GetServiceZIRefreshToken($zoho_inventory_refresh_token);
            $zoho_inventory_access_token = $respoAtJs['access_token'];
            update_option('zoho_inventory_access_token', $respoAtJs['access_token']);
            update_option('zoho_inventory_timestamp', strtotime(date('Y-m-d H:i:s')) + $respoAtJs['expires_in']);
        }

        $curl = curl_init($url);
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer " . $zoho_inventory_access_token,
                ),

            )
        );
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        $response = curl_exec($curl);
        $json = json_decode($response);

        return $json;

    }

    // Get Image Zoho

    public function ExecuteCurlCallImageGet($url, $image_name)
    {

        $handlefunction = new Classfunctions;

        $zoho_inventory_access_token = $this->config['ExecutecallZI']['ATOKEN'];
        $zoho_inventory_refresh_token = $this->config['ExecutecallZI']['RTOKEN'];
        $zoho_inventory_timestamp = $this->config['ExecutecallZI']['EXPIRESTIME'];

        $current_time = strtotime(date('Y-m-d H:i:s'));

        if ($zoho_inventory_timestamp < $current_time) {

            $respoAtJs = $handlefunction->GetServiceZIRefreshToken($zoho_inventory_refresh_token);

            $zoho_inventory_access_token = $respoAtJs['access_token'];
            update_option('zoho_inventory_access_token', $respoAtJs['access_token']);
            update_option('zoho_inventory_timestamp', strtotime(date('Y-m-d H:i:s')) + $respoAtJs['expires_in']);

        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $zoho_inventory_access_token,
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $upload = wp_upload_dir();
        $absolute_upload_path = $upload['basedir'] . '/zoho_image/';
        $url_upload_path = $upload['baseurl'] . '/zoho_image/';

        $img = '/' . rand() . $image_name;
        //$img = '/image'.rand().'.jpg';
        $upload_dir = $absolute_upload_path . $img;

        if (!is_dir($absolute_upload_path)) {
            mkdir($absolute_upload_path);
        }

        file_put_contents($upload_dir, $response);

        return $url_upload_path . $img;

    }
}
