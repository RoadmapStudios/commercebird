<?php


use com\zoho\api\authenticator\OAuthBuilder;
use com\zoho\crm\api\dc\EUDataCenter;
use com\zoho\crm\api\InitializeBuilder;
use com\zoho\crm\api\taxes\APIException;
use com\zoho\crm\api\taxes\ResponseWrapper;
use com\zoho\crm\api\taxes\TaxesOperations;
use com\zoho\crm\api\util\Choice;

class GetTaxes {

	private array $config;

	public function __construct() {
		$this->config = array(
			'ExecutecallZCRM' => array(
				'ATOKEN' => get_option( 'zoho_crm_access_token' ),
				'RTOKEN' => get_option( 'zoho_crm_refresh_token' ),
				'CLIENTID' => get_option( 'zoho_crm_client_id' ),
				'CLIENTSECRET' => get_option( 'zoho_crm_client_secret' ),
			),
		);
	}

	public function initialize() {
		$environment = EUDataCenter::SANDBOX();
		$token = ( new OAuthBuilder() )
			->clientId( $this->config['ExecutecallZCRM']['CLIENTID'] )
			->clientSecret( $this->config['ExecutecallZCRM']['CLIENTSECRET'] )
			->refreshToken( $this->config['ExecuteCallZCRM']['RTOKEN'] )
			->build();
		( new InitializeBuilder() )
			->environment( $environment )
			->token( $token )
			->initialize();
	}

	/**
	 * <h3> Get Taxes </h3>
	 * This method is used to get all the Organization Taxes and print the response.
	 * @throws Exception
	 */
	public function getTaxes() {
		$taxesOperations = new TaxesOperations();
		//Call getTaxes method
		$response = $taxesOperations->getTaxes();
		if ( $response != null ) {
			echo ( "Status code " . $response->getStatusCode() . "\n" );
			if ( in_array( $response->getStatusCode(), array( 204, 304 ) ) ) {
				echo ( $response->getStatusCode() == 204 ? "No Content\n" : "Not Modified\n" );
				return;
			}
			$responseHandler = $response->getObject();
			if ( $responseHandler instanceof ResponseWrapper ) {
				$responseWrapper = $responseHandler;
				$orgTax = $responseWrapper->getOrgTaxes();
				$taxes = $orgTax->getTaxes();
				if ( $taxes != null ) {
					foreach ( $taxes as $tax ) {
						echo ( "Tax DisplayLabel: " . $tax->getDisplayLabel() . "\n" );
						echo ( "Tax Name: " . $tax->getName() . "\n" );
						echo ( "Tax Id: " . $tax->getId() . "\n" );
						echo ( "Tax Value: " . $tax->getValue() . "\n" );
					}
				}
				$preference = $orgTax->getPreference();
				if ( $preference != null ) {
					echo ( "Preference AutoPopulateTax: " );
					print_r( $preference->getAutoPopulateTax() );
					echo ( "\n" );
					echo ( "Preference ModifyTaxRates: " );
					print_r( $preference->getModifyTaxRates() );
					echo ( "\n" );
				}
			} else if ( $responseHandler instanceof APIException ) {
				$exception = $responseHandler;
				echo ( "Status: " . $exception->getStatus()->getValue() . "\n" );
				echo ( "Code: " . $exception->getCode()->getValue() . "\n" );
				echo ( "Details: " );
				foreach ( $exception->getDetails() as $key => $value ) {
					echo ( $key . " : " . $value . "\n" );
				}
				echo ( "Message : " . ( $exception->getMessage() instanceof Choice ? $exception->getMessage()->getValue() : $exception->getMessage() ) );
			}
		}
	}
}
$get_taxes = new GetTaxes();
$get_taxes->initialize();
$get_taxes->getTaxes();
// GetTaxes::getTaxes();
