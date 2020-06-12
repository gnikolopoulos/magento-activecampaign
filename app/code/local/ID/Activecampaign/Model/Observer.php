<?php

class ID_Activecampaign_Model_Observer
{

	/**
	 * Updates an ecommerce contact
	 * @param  Varien_Event_Observer $observer Magento Observer instance
	 * @return boolean
	 */
	public function customerSaveAfter(Varien_Event_Observer $observer)
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$customer_object = $observer->getCustomer();
			$customer = Mage::helper('id_activecampaign/api')->customerExists( $customer_object->getEmail() );
			$contact = Mage::helper('id_activecampaign/api')->contactExists( $customer_object->getEmail() );

			$contact_payload = array(
				'email' => $customer_object->getEmail(),
				'firstName' => $customer_object->getFirstname(),
				'lastName' => $customer_object->getLastname()
			);
			if( !$contact ) {
				$contact = Mage::helper('id_activecampaign')->createContact( $contact_payload );
			} else {
				$contact = Mage::helper('id_activecampaign')->updateContact( $contact, $contact_payload );
			}

			if( $contact ) {
				$fieldId = Mage::getStoreConfig('id_activecampaign/field_mapping/customer_group');
				$group = Mage::getModel('customer/group')->load( $customer_object->getGroupId() )->getCustomerGroupCode();
				Mage::helper('id_activecampaign')->updateCustomFieldValue($contact, $fieldId, $group);
			}

			if( !$customer ) {
				$customer = Mage::helper('id_activecampaign')->createEcomCustomer( $customer_object->getId(), $customer_object->getEmail() );
			}

			if( $customer ) {
				$customer_object->setActivecampaignSync('1');
				$customer_object->save();
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * Deletes an existing Ecommerce Customer from ActiveCampaign
	 * @param  Varien_Event_Observer $observer Magento Observer instance
	 * @return boolean
	 */
	public function customerDeleteAfter(Varien_Event_Observer $observer)
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$customer = $observer->getCustomer();

			if( $cid = Mage::helper('id_activecampaign/api')->contactExists($customer->getEmail()) ) {
				if( Mage::helper('id_activecampaign')->deleteContact($cid) ) {
					return true;
				} else {
					return false;
				}
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
			$subscriber = $observer->getEvent()->getSubscriber();
			$existing = Mage::helper('id_activecampaign/api')->contactExists( $subscriber->getSubscriberEmail() );

			if( $subscriber->isSubscribed() ) {
				if( !$existing ) {
					$res = Mage::helper('id_activecampaign')->createContact( Mage::helper('id_activecampaign/transformer')->createContactPayloadFromSubscriber($subscriber) );
				}
			} else {
				if( $existing ) {
					$res = Mage::helper('id_activecampaign')->deleteContact($existing);
				}
			}

			if( $res ) {
				return true;
			} else {
				return true;
			}
		} else {
			return true;
		}
	}

}