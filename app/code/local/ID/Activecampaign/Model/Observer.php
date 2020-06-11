<?php

require Mage::getModuleDir('', 'ID_Activecampaign') . '/lib/vendor/autoload.php';

use GuzzleHttp\Client;

class ID_Activecampaign_Model_Observer
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
	 * Creates or updates a ecommerce customer
	 * @param  Varien_Event_Observer $observer Magento Observer instance
	 * @return boolean
	 */
	public function customerSaveAfter(Varien_Event_Observer $observer)
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$this->__init();
			$customer = $observer->getCustomer()->getData();

			$payload = array(
				'connectionid' => Mage::getStoreConfig('id_activecampaign/config/connection_id'),
				'externalid' => $customer['entity_id'],
				'acceptsMarketing' => $customer['is_subscribed'] ? "1":"0",
				'email' => $customer['email'],
			);

			$params = array(
				'headers' => $this->headers,
				'json' => array( 'ecomCustomer' => $payload ),
			);

			if( $cid = Mage::helper('id_activecampaign/api')->customerExists($customer['email']) ) {
				$res = $this->client->put('/api/3/ecomCustomers/'.$cid, $params);
			} else {
				$res = $this->client->post('/api/3/ecomCustomers', $params);
			}

			$result = json_decode($res->getBody());

			if( $res->getStatusCode() == 201 ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * Create a new order in ActiveCampaign
	 * @param  Varien_Event_Observer $observer Magento Observer instance
	 * @return boolean
	 */
	public function salesOrderPlaceAfter(Varien_Event_Observer $observer)
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$order = $observer->getData('order');
			if( !Mage::helper('id_activecampaign/api')->orderExists( $order->getId() ) ) {
				$result = Mage::helper('id_activecampaign')->createOrder( $order );

				if( $result ) {
					$order->setActivecampaignSync('1');
					$order->save();
				}
			} else {
				$order->setActivecampaignSync('1');
				$order->save();
			}
		} else {
			return true;
		}
	}

	/**
	 * Creates or deletes an order from ActiveCampaign based on status
	 * @param  Varien_Event_Observer $observer Magento Observer instance
	 * @return boolean
	 */
	public function salesOrderUpdate(Varien_Event_Observer $observer)
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$order = $observer->getData('order');

			if( !Mage::helper('id_activecampaign/api')->orderExists($order->getId()) ) {
				if( $order->getState() !== Mage_Sales_Model_Order::STATE_CANCELED ) {
					$result = Mage::helper('id_activecampaign')->createOrder( $order );

					if( $result ) {
						$order->setActivecampaignSync('1');
						$order->save();
					}
				}
			}
		} else {
			return true;
		}
	}

	public function salesOrderPaymentCancel(Varien_Event_Observer $observer)
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$payment = $observer->getEvent()->getPayment();
			$order = $payment->getOrder();

			return Mage::helper('id_activecampaign')->deleteOrder( $order );
		} else {
			return true;
		}
	}

	/**
	 * Add or delete a contact based on subscription status
	 * @param  Varien_Event_Observer $observer Magento Observer instance
	 * @return boolean
	 */
	public function subscribedToNewsletter(Varien_Event_Observer $observer)
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$this->__init();
			$subscriber = $observer->getEvent()->getSubscriber();
			$existing = Mage::helper('id_activecampaign/api')->contactExists( $subscriber->getSubscriberEmail() );

			if( $subscriber->isSubscribed() ) {
				if( !$existing ) {
					$payload = array(
						'email' => $subscriber->getSubscriberEmail(),
					);

					$params = array(
						'headers' => $this->headers,
						'json' => array( 'contact' => $payload ),
					);

					$res = $this->client->post('/api/3/contacts', $params);
				}
			} else {
				if( $existing ) {
					$params = array(
						'headers' => $this->headers,
					);

					$res = $this->client->delete('/api/3/contacts/'.$existing, $params);
				}
			}

			if( $res ) {
				if( $res->getStatusCode() == 201 || $res->getStatusCode() == 200 ) {
					return true;
				} else {
					return false;
				}
			} else {
				return true;
			}
		} else {
			return true;
		}
	}

}