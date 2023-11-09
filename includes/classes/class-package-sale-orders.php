<?php 

/**
 * All package of sales orders related functions.
 *
 * @package  WooZo Inventory
 * @category Zoho Integration
 * @author   Roadmap Studios
 * @link     https://roadmapstudios.com
 */

class PackageClass {
	
	public function __construct()
	{
		$config =  [
			
			'PackageZI'	=> [
					'OID'=>get_option('zoho_inventory_oid'),
					'APIURL'=>get_option('zoho_inventory_url'),
					
			]
			
		];

		return $this->config = $config;

	}
	
	function PackageCreateFunction($order_id, $json){
		// $fd = fopen(__DIR__.'/PackageCreateFunction.txt','w+');
		
		$shipDate = '';
	
		foreach ( $json->salesorder as $key => $value ) {

			if ( $key == 'salesorder_id' ) {
				
				$salesorder_id = $value;

			}	
			if ( $key == 'line_items' ) {
											
				if($key == 'date'){
					$shipDate = $value;
				}
				if($key == 'salesorder_number'){
					$package_number = $value;
				}
					
				foreach($value as $kk => $vv){
					
					$lineItems[] = '{"so_line_item_id": "' . $vv->line_item_id .'","quantity": "' . $vv->quantity .'"}';
					
				}
				$impot = implode( ',', $lineItems );
				
				$json_package= '"date": "'.$shipDate.'","line_items": ['.$impot.']';
				
				$zoho_inventory_oid = $this->config['PackageZI']['OID'];
				$zoho_inventory_url = $this->config['PackageZI']['APIURL'];
				
				$url_package = $zoho_inventory_url . 'api/v1/packages?salesorder_id='.$salesorder_id;

				$data3             = array(
						'JSONString'      => '{' . $json_package . '}',
						'organization_id' => $zoho_inventory_oid,
					);

				
				$executeCurlCallHandle = new ExecutecallClass();
				$package_json = $executeCurlCallHandle->ExecuteCurlCallPost($url_package, $data3);

				if($package_json->code == 0){
					
					foreach ( $package_json->package as $key3 => $value3 ) {
						
						if ( $key3 == 'package_id' ) {
							$package_id = $value3;
							// fwrite($fd, PHP_EOL. $package_id); //logging response
							update_post_meta( $order_id, 'zi_package_id', $package_id );
						}
						if ( $key3 == 'line_items' ) {
							
							foreach($value3 as $key2 => $value2){	
								$k1[] = $value2->line_item_id;
								$k2[] = $value2->so_line_item_id;
								$k3[] = $value2->item_id;
							}
							
							$k11 = implode(',',$k1);
							$k12 = implode(',',$k2);
							$k13 = implode(',',$k3);
							
							update_post_meta( $order_id, 'package_line_item_id', $k11);	
							update_post_meta( $order_id, 'package_so_line_item_id', $k12);
							update_post_meta( $order_id, 'package_item_id', $k13);
							
						}

					}
					
				}

			}
		}
		// fclose($fd); //end of logging
		
		return $package_json;
	}

} // end of class
?>