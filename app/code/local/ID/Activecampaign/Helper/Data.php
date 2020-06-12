<?php

require Mage::getModuleDir('', 'ID_Activecampaign') . '/lib/vendor/autoload.php';

use GuzzleHttp\Client;

class ID_Activecampaign_Helper_Data extends Mage_Core_Helper_Abstract
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
	 * Create an order in ActiveCampaign
	 * @param  object $order Order object
	 * @return boolean
	 */
	public function createOrder($order)
	{
		$this->__init();
		$customer = Mage::helper('id_activecampaign/api')->customerExists( $order->getCustomerEmail() );
		$contact = Mage::helper('id_activecampaign/api')->contactExists( $order->getCustomerEmail() );

		if( !$contact ) {
			$contact = $this->createContact( Mage::helper('id_activecampaign/transformer')->createContactPayloadFromOrder($order) );
		} else {
			$contact = $this->updateContact( $contact, Mage::helper('id_activecampaign/transformer')->createContactPayloadFromOrder($order) );
		}

		if( $contact ) {
			$fieldId = Mage::getStoreConfig('id_activecampaign/field_mapping/customer_group');
			$group = Mage::getModel('customer/group')->load( $order->getCustomerGroupId() )->getCustomerGroupCode();
			$this->updateCustomFieldValue($contact, $fieldId, $group);
		}

		if( !$customer ) {
			$customerId = $order->getCustomerId() ?: 'cus_'.$order->getIncrementId();
			$customer = $this->createEcomCustomer( $customerId, $order->getCustomerEmail() );
		}

		try {
			$params = array(
				'headers' => $this->headers,
				'json' => array( 'ecomOrder' => Mage::helper('id_activecampaign/transformer')->createOrderPayload( $order ) ),
			);

			$res = $this->client->post('/api/3/ecomOrders', $params);

			if( $res->getStatusCode() == 201 ) {
				return true;
			} else {
				return false;
			}
		} catch( GuzzleHttp\Exception\ClientException $e ) {
			return true;
		}
	}

	/**
	 * Create a new abandoned cart in ActiveCampaign
	 * @param  object $quote Quote object
	 * @return boolean
	 */
	public function createAbandonedCart($quote)
	{
		$this->__init();

		$customer = Mage::helper('id_activecampaign/api')->customerExists( $quote->getCustomerEmail() );
		$contact = Mage::helper('id_activecampaign/api')->contactExists( $quote->getCustomerEmail() );

		if( !$contact ) {
			$contact = $this->createContact( $quote );
		}

		if( $contact ) {
			$fieldId = Mage::getStoreConfig('id_activecampaign/field_mapping/customer_group');
			$group = Mage::getModel('customer/group')->load( $order->getCustomerGroupId() )->getCustomerGroupCode();
			$this->updateCustomFieldValue($contact, $fieldId, $group);
		}

		if( !$customer ) {
			$customerId = $quote->getCustomerId();
			$customer = $this->createEcomCustomer( $customerId, $quote->getCustomerEmail() );
		}

		try {
			$params = array(
				'headers' => $this->headers,
				'json' => array( 'ecomOrder' => Mage::helper('id_activecampaign/transformer')->createAbandonedCartPayload( $quote ) ),
			);

			$res = $this->client->post('/api/3/ecomOrders', $params);

			if( $res->getStatusCode() == 201 ) {
				return true;
			} else {
				return false;
			}
		} catch( GuzzleHttp\Exception\ClientException $e ) {
			return true;
		}
	}

	/**
	 * Delte an order from ActiveCampaign
	 * @param  object $order Order object
	 * @return boolean
	 */
	public function deleteOrder($order)
	{
		$this->__init();
		$existing = Mage::helper('id_activecampaign/api')->orderExists($order->getId());

		if( $existing ) {
			$params = array(
				'headers' => $this->headers,
			);
			$res = $this->client->delete('/api/3/ecomOrders/'.$existing, $params);
			$result = json_decode($res->getBody());

			if( $res->getStatusCode() == 200 ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * Create an ecommerce customer inside ActiveCampaign
	 * @param  object $order 	  Order object from magento observer
	 * @return int|boolean        Customer id or flase on error
	 */
	public function createEcomCustomer($customerId, $email)
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$this->__init();

			$payload = array(
				'connectionid' => Mage::getStoreConfig('id_activecampaign/config/connection_id'),
				'externalid' => $customerId,
				'acceptsMarketing' => "1",
				'email' => $email,
			);

			try {
				$params = array(
					'headers' => $this->headers,
					'json' => array( 'ecomCustomer' => $payload ),
				);

				$res = $this->client->post('/api/3/ecomCustomers', $params);
				$result = json_decode($res->getBody());

				if( $res->getStatusCode() == 201 ) {
					return intval($result->meta->total) > 0 ? $result->ecomCustomers{0}->id : false;
				} else {
					return false;
				}
			} catch( GuzzleHttp\Exception\ClientException $e ) {
				return true;
			}
		} else {
			return false;
		}
	}

	public function deleteEcomCustomer($customerId)
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$this->__init();

			try {
				$params = array(
					'headers' => $this->headers,
				);

				$res = $this->client->post('/api/3/ecomCustomers/'.$customerId, $params);

				if( $res->getStatusCode() == 200 ) {
					return true;
				} else {
					return false;
				}
			} catch( GuzzleHttp\Exception\ClientException $e ) {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Create a contact inside ActiveCampaign
	 * @param  array $data 		  Contact data array
	 * @return int|boolean        Contact id or flase on error
	 */
	public function createContact($data)
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$this->__init();

			$params = array(
				'headers' => $this->headers,
				'json' => array( 'contact' => $data ),
			);

			try {
				$res = $this->client->post('/api/3/contacts', $params);
				$result = json_decode($res->getBody());

				if( $res->getStatusCode() == 201 ) {
					return intval($result->meta->total) > 0 ? $result->contacts{0}->id : false;
				} else {
					return false;
				}
			} catch( GuzzleHttp\Exception\ClientException $e ) {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Update an existing contact inside ActiveCampaign
	 * @param  array $data 		  Contact data array
	 * @return int|boolean        Contact id or flase on error
	 */
	public function updateContact($contactId, $data)
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$this->__init();

			$params = array(
				'headers' => $this->headers,
				'json' => array( 'contact' => $data ),
			);

			try {
				$res = $this->client->put('/api/3/contacts/'.$contactId, $params);
				$result = json_decode($res->getBody());

				if( $res->getStatusCode() == 200 ) {
					return intval($result->meta->total) > 0 ? $result->contacts{0}->id : false;
				} else {
					return false;
				}
			} catch( GuzzleHttp\Exception\ClientException $e ) {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Deletes an existing contact from ActiveCampaign
	 * @param  int $contactId 		  Contact id
	 * @return boolean
	 */
	public function deleteContact($contactId)
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$this->__init();

			$params = array(
				'headers' => $this->headers,
			);

			try {
				$res = $this->client->delete('/api/3/contacts/'.$contactId, $params);

				if( $res->getStatusCode() == 200 ) {
					return true;
				} else {
					return false;
				}
			} catch( GuzzleHttp\Exception\ClientException $e ) {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Creates a new connection to ActiveCmapaign
	 * @return int|boolean connection id or false on error
	 */
	public function createConnection()
	{
		$this->__init();

		$params = array(
			'headers' => $this->headers,
			'json' => array( 'connection' => array(
				'service' => 'magento',
				'externalid' => 'magento',
				'name' => Mage::app()->getStore()->getFrontendName(),
				'logoUrl' => Mage::getStoreConfig('design/header/logo_src', Mage::app()->getStore()->getStoreId()),
				'linkUrl' => Mage::getStoreConfig('web/secure/base_url', Mage::app()->getStore()->getStoreId()),
			) ),
		);

		$res = $this->client->post('/api/3/connections', $params);
		$result = json_decode($res->getBody());

		if( $res->getStatusCode() == 200 ) {
			return $result->connection->id;
		} else {
			return false;
		}
	}

	/**
	 * Gets all custom fields from ActiveCampaign
	 * @return object|boolean fields object or false on error
	 */
	public function getCustomFields()
	{
		$this->__init();

		$params = array(
			'headers' => $this->headers,
		);

		$res = $this->client->get('/api/3/fields', $params);
		$result = json_decode($res->getBody());

		if( $res->getStatusCode() == 200 ) {
			return $result->fields;
		} else {
			return false;
		}
	}

	/**
	 * Updates or creates a custom field value for a given contact id
	 * @param  int 	  $contactId 	Contact Id
	 * @param  int 	  $fieldId   	Field Id to update
	 * @param  string $value     	Text value of custom field
	 * @return boolean
	 */
	public function updateCustomFieldValue($contactId, $fieldId, $value)
	{
		$this->__init();

		$params = array(
			'headers' => $this->headers,
			'json' => array( 'fieldValue' => array(
				'contact' => $contactId,
				'field' => $fieldId,
				'value' => $value
			) ),
		);

		$res = $this->client->post('/api/3/fieldValues', $params);

		if( $res->getStatusCode() == 200 ) {
			return true;
		} else {
			return false;
		}
	}
}