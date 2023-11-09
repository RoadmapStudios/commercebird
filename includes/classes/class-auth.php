<?php
 
class Classfunctions {

	/**
	 * @var array|array[]
	 */
	private array $config;

	public function __construct()
	{
		$config =  [
			
			'ServiceZI'	=> [
					'OID'=>get_option('zoho_inventory_oid'),
					'CLIENTSECRET'=>get_option('zoho_inventory_cs'),
					'CLIENTID'=>get_option('zoho_inventory_cid'),
					'REDIRECTURL'=>get_option('authorization_redirect_uri'),
					'APIURL'=>get_option('zoho_inventory_url'),
					'DOMAINNAME' =>get_option('zoho_inventory_domain'),
					'SCOPE' =>'ZohoInventory.FullAccess.all',
					//'STATE' => wp_create_nonce('redirect_url'),
					'AUTHURL' =>"https://accounts.zoho.".get_option('zoho_inventory_domain')."/oauth/v2/token",
					
			]
			
		];

		return $this->config = $config;

	}
	
	function GetServiceZIAccessToken($code) {		
	
		$headers = array("Content-Type: application/x-www-form-urlencoded");
		$params = array(
		"code" => $code,				
		"client_id" => $this->config['ServiceZI']['CLIENTID'],
		"client_secret" =>$this->config['ServiceZI']['CLIENTSECRET'],
		"redirect_uri" => $this->config['ServiceZI']['REDIRECTURL'],
		"scope" => $this->config['ServiceZI']['SCOPE'],
		"grant_type" =>"authorization_code",
		//"state" =>$this->config['ServiceZI']['STATE'],
		);
		$curl = curl_init();                  
		$url = $this->config['ServiceZI']['AUTHURL'];
		curl_setopt($curl, CURLOPT_URL,$url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);				
		curl_setopt($curl, CURLOPT_POST, true); 
		curl_setopt($curl, CURLOPT_POSTFIELDS,http_build_query($params));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec ($curl);
		return json_decode($output, true);
	}
	
	
	//get refresh token in ServiceZI 
	function GetServiceZIRefreshToken($refresh_token) {		
		$headers = array("Content-Type: application/x-www-form-urlencoded");
		$params = array(
			"refresh_token"=> $refresh_token,
			"grant_type"=> 'refresh_token',
			"client_id"=> $this->config['ServiceZI']['CLIENTID'],
			"client_secret"=> $this->config['ServiceZI']['CLIENTSECRET']
		);
		$curl = curl_init();                  
		$url = $this->config['ServiceZI']['AUTHURL'];
		curl_setopt($curl, CURLOPT_URL,$url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);				
		curl_setopt($curl, CURLOPT_POST, true); 
		curl_setopt($curl, CURLOPT_POSTFIELDS,http_build_query($params));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec ($curl);
	
		return json_decode($output, true);
	}
	
}
	
$handlefunction = new Classfunctions;
 