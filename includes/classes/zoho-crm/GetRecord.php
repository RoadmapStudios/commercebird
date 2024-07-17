<?php

use com\zoho\api\authenticator\OAuthBuilder;
use com\zoho\crm\api\dc\USDataCenter;
use com\zoho\crm\api\InitializeBuilder;
use com\zoho\crm\api\HeaderMap;
use com\zoho\crm\api\ParameterMap;
use com\zoho\crm\api\layouts\Layouts;
use com\zoho\crm\api\record\APIException;
use com\zoho\crm\api\record\FileBodyWrapper;
use com\zoho\crm\api\record\Record;
use com\zoho\crm\api\record\GetRecordParam;
use com\zoho\crm\api\record\RecordOperations;


class GetRecord {
	/**
	 * @var array|array[]
	 */
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
		$environment = USDataCenter::PRODUCTION();
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

	public function getRecord( $module, $record_id, $woo_object_id ) {
		$recordOperations = new RecordOperations( $module );
		$paramInstance = new ParameterMap();
		$paramInstance->add( GetRecordParam::approved(), "false" );
		$paramInstance->add( GetRecordParam::converted(), "false" );
		$fieldNames = array( "Deal_Name", "Company" );
		foreach ( $fieldNames as $fieldName ) {
			$paramInstance->add( GetRecordParam::fields(), $fieldName );
		}
		$startdatetime = date_create( "2020-06-27T15:10:00" );
		$paramInstance->add( GetRecordParam::startDateTime(), $startdatetime );
		$enddatetime = date_create( "2020-06-29T15:10:00" );
		$paramInstance->add( GetRecordParam::endDateTime(), $enddatetime );
		$paramInstance->add( GetRecordParam::territoryId(), "34770613051357" );
		$paramInstance->add( GetRecordParam::includeChild(), "true" );
		$headerInstance = new HeaderMap();
		$response = $recordOperations->getRecord( $recordId, $paramInstance, $headerInstance );
        $responseJson = $response->getResponseJSON();
	}

}
