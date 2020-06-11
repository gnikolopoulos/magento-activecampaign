<?php

class ID_Activecampaign_Adminhtml_ActivecampaignController extends Mage_Adminhtml_Controller_Action
{

	public function indexAction()
	{
		$this->_redirectReferer();
		Mage::getSingleton('core/session')->addNotice('You cannot access this area directly');
		return $this;
	}

	public function createConnectionAction()
	{
		/*
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') == '' ) {
			$res = $this->client->post('/api/3/connections', $params);
		} else {
			$res = $this->client->put('/api/3/connections/'.Mage::getStoreConfig('id_activecampaign/config/connection_id'), $params);
		}
		*/

		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') == '' ) {
			if( Mage::getStoreConfig('id_activecampaign/config/api_endpoint') != '' && Mage::getStoreConfig('id_activecampaign/config/api_key') != '' ) {
				$result = Mage::helper('id_activecampaign')->createConnection();
			} else {
				Mage::getSingleton('core/session')->addNotice('API endpoint and/or API key fields are empty. Fill them and save settings before creating a connection.');
			}
		} else {
			Mage::getSingleton('core/session')->addNotice('Connection already exists: '.Mage::getStoreConfig('id_activecampaign/config/connection_id') );
		}

		if( $result ) {
			Mage::getSingleton('core/session')->addNotice('Connection created. Connection id: '.$result->connection->id);
			Mage::getConfig()->saveConfig('id_activecampaign/config/connection_id', $result->connection->id, Mage::app()->getStore()->getStoreId(), 0);
		} else {
			if( count($result->errors) > 0 ) {
				Mage::getSingleton('core/session')->addError($result->errors{0}->title);
			}
		}

		$this->_redirectReferer();
		return $this;
	}

	public function activecampaignAction() {
		$order = Mage::getModel("sales/order")->load( $this->getRequest()->getParam('order') );

		if( !Mage::helper('id_activecampaign/api')->orderExists($order->getId()) ) {
			$result = Mage::helper('id_activecampaign')->createOrder( $order );

			if( $result ) {
				$this->_redirectReferer();
				Mage::getSingleton('core/session')->addSuccess('Order sent to ActiveCampaign');
			} else {
				$this->_redirectReferer();
				Mage::getSingleton('core/session')->addError('Errors sending data to Active Campaign');
			}
		} else {
			if( $order->getState() === Mage_Sales_Model_Order::STATE_CANCELED ) {
				$result = Mage::helper('id_activecampaign')->deleteOrder( $order );

				$this->_redirectReferer();
				Mage::getSingleton('core/session')->addNotice('Order deleted from ActiveCampaign');
			} else {
				$this->_redirectReferer();
				Mage::getSingleton('core/session')->addNotice('Order already exists in ActiveCampaign');
			}
		}

		return $this;
	}

}