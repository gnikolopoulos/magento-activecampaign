<?php

require Mage::getModuleDir('', 'ID_Activecampaign') . '/lib/vendor/autoload.php';

use GuzzleHttp\Client;

class ID_Activecampaign_Helper_Api extends Mage_Core_Helper_Abstract
{
	private $client;
	private $headers;

	/**
	 * Create the GuzzleHTTP Client and headers to be used
	 */
	private function __init()
	{
		$this->client = new Client([ 'base_uri' => Mage::getStoreConfig('id_activecampaign/config/api_endpoint') ]);
		$this->headers = array('Api-Token' => Mage::getStoreConfig('id_activecampaign/config/api_key'));
	}

	/**
	 * Returns true if a given irder id already exists in ActiveCampaign
	 * @param  int $orderId Order ID (not increment id)
	 * @return boolean
	 */
	public function orderExists($orderId)
	{
		$this->__init();
		$params = array(
			'headers' => $this->headers,
			'query' => array(
				'filters' => array(
					'externalid' => $orderId
				)
			)
		);

		$res = $this->client->get('/api/3/ecomOrders', $params);
		$result = json_decode($res->getBody());

		if( $res->getStatusCode() == 200 ) {
			return intval($result->meta->total) > 0 ? $result->ecomOrders{0}->id : false;
		} else {
			return false;
		}
	}

	/**
	 * Returns customer id for a given external id
	 * @param  int $email Customer Email
	 * @return int|boolean     id or false if customer does not exist
	 */
	public function customerExists($email)
	{
		$this->__init();
		$params = array(
			'headers' => $this->headers,
			'query' => array(
				'filters' => array(
					'email' => $email,
					'connectionid' => Mage::getStoreConfig('id_activecampaign/config/connection_id')
				)
			)
		);

		$res = $this->client->get('/api/3/ecomCustomers', $params);
		$result = json_decode($res->getBody());

		if( $res->getStatusCode() == 200 ) {
			return intval($result->meta->total) > 0 ? $result->ecomCustomers{0}->id : false;
		} else {
			return false;
		}
	}

	/**
	 * Returns contact id for a given external id
	 * @param  int $email Customer Email
	 * @return int|boolean     id or false if customer does not exist
	 */
	public function contactExists($email)
	{
		$this->__init();
		$params = array(
			'headers' => $this->headers,
			'query' => array(
				'filters' => array(
					'email' => $email
				)
			)
		);

		$res = $this->client->get('/api/3/contacts', $params);
		$result = json_decode($res->getBody());

		if( $res->getStatusCode() == 200 ) {
			return intval($result->meta->total) > 0 ? $result->contacts{0}->id : false;
		} else {
			return false;
		}
	}
}