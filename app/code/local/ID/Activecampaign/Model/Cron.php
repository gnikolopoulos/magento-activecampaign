<?php

class ID_Activecampaign_Model_Cron
{
	public function syncOrders()
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$orders = Mage::getModel('sales/order')->getCollection()
				->addAttributeToSort('increment_id', 'DESC')
				->addFieldToFilter('status', array('nin' => array('canceled','closed')))
				->addFieldToFilter('store_id',array('in' => explode(',', Mage::getStoreConfig('id_activecampaign/cron_settings/store_filter'))))
				->addAttributeToFilter('activecampaign_sync', array('null' => true))
				->setPageSize( Mage::getStoreConfig('id_activecampaign/cron_settings/batch_size') )
				->setCurPage(1);

			foreach( $orders as $order ) {
				if( !Mage::helper('id_activecampaign/api')->orderExists( $order->getId() ) ) {
					$result = Mage::helper('id_activecampaign')->createOrder( $order );

					if( $result ) {
						try {
							$order->setActivecampaignSync('1');
							$order->save();
						} catch( Exception $e ) {
							$resource = Mage::getSingleton('core/resource');
							$writeConnection = $resource->getConnection('core_write');
							$table = $resource->getTableName('sales/order');
							$query = "UPDATE {$table} SET activecampaign_sync = '1' WHERE entity_id = {$order->getId()}; ";
							$writeConnection->query($query);
						}
					}
				} else {
					try {
						$order->setActivecampaignSync('1');
						$order->save();
					} catch( Exception $e ) {
						$resource = Mage::getSingleton('core/resource');
						$writeConnection = $resource->getConnection('core_write');
						$table = $resource->getTableName('sales/order');
						$query = "UPDATE {$table} SET activecampaign_sync = '1' WHERE entity_id = {$order->getId()}; ";
						$writeConnection->query($query);
					}
				}
			}
		}
	}

	public function syncCustomers()
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$customers = Mage::getModel('customer/customer')->getCollection()
				->addAttributeToSort('entity_id', 'ASC')
				->addAttributeToSelect('*')
				->addAttributeToFilter('activecampaign_sync', array('null' => true), 'left')
				->setPageSize( Mage::getStoreConfig('id_activecampaign/cron_settings/batch_size') )
				->setCurPage(1);

			foreach( $customers as $customer ) {
				if( filter_var($customer->getEmail(), FILTER_VALIDATE_EMAIL) ) {
					if( !Mage::helper('id_activecampaign/api')->customerExists( $customer->getEmail() ) ) {
						$result = Mage::helper('id_activecampaign')->createEcomCustomer( $customer->getId(), $customer->getEmail() );

						if( $result ) {
							$customer->setActivecampaignSync('1');
							$customer->save();
						}
					} else {
						$customer->setActivecampaignSync('1');
						$customer->save();
					}
				} else {
					$customer->setActivecampaignSync('1');
					$customer->save();
				}
			}
		}
	}

	public function syncAbandonedCarts()
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			$carts = Mage::getModel('sales/quote')->getCollection()
				->setOrder('entity_id', 'DESC')
		        ->addFieldToFilter('is_active', '1')
		        ->addFieldToFilter('items_qty', array('gt' => 1))
		        ->addFieldToFilter('store_id',array('in' => explode(',', Mage::getStoreConfig('id_activecampaign/cron_settings/store_filter'))))
		        ->addFieldToFilter('activecampaign_sync', array('null' => true))
		        ->addFieldToFilter('customer_email', array('notnull' => true))
				->setPageSize( Mage::getStoreConfig('id_activecampaign/cron_settings/batch_size') )
				->setCurPage(1);

			foreach( $carts as $cart ) {
				$result = Mage::helper('id_activecampaign')->createAbandonedCart( $cart );

				if( $result ) {
					try {
						$cart->setActivecampaignSync('1');
						$cart->save();
					} catch( Exception $e ) {
						$resource = Mage::getSingleton('core/resource');
						$writeConnection = $resource->getConnection('core_write');
						$table = $resource->getTableName('sales/quote');
						$query = "UPDATE {$table} SET activecampaign_sync = '1' WHERE entity_id = {$cart->getId()}; ";
						$writeConnection->query($query);
					}
				}
			}
		}
	}

	public function syncSubscribers()
	{
		if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
			//
		}
	}
}