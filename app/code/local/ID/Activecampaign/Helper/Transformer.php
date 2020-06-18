<?php

class ID_Activecampaign_Helper_Transformer extends Mage_Core_Helper_Abstract
{
	/**
	 * Prepares a payload array for a given order
	 * @param  object $order The order object
	 * @return array         Payload data
	 */
	public function createOrderPayload($order)
	{
		$order_products = array();
		foreach( $order->getAllVisibleItems() as $item ) {
			if($item->getProductType() == 'configurable') {
				$product = $item->getProduct();
			} else {
				$product = Mage::getModel('catalog/product')->setStoreId($item->getOrder()->getStoreId())->load($item->getProductId());
			}
			$order_products[] = array(
				'externalid' => $item->product_id,
				'name' => $item->name,
				'price' => 100 * number_format($item->price_incl_tax, 2, '.', ''),
				'quantity' => intval( $item->getQtyOrdered() ),
				'category' => "",
				'sku' => $item->sku,
				'description' => $item->description,
				'imageUrl' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage(),
				'productUrl' => "",
			);
		}

		$payload = array(
			'externalid' => $order->getId(),
			'source' => "1",
			'email' => $order->getCustomerEmail() ?: $order->getBillingAddress()->getEmail(),
			'orderProducts' => $order_products,
			'shippingMethod' => $order->getShippingMethod(),
			'totalPrice' =>  100 * number_format($order->getGrandTotal(), 2, '.', ''),
			'externalCreatedDate' => $order->getCreatedAt(),
			'shippingAmount' => 100 * number_format($order->getShippingAmount(), 2, '.', ''),
			'taxAmount' => 100 * number_format($order->getBillingAddress()->getData('tax_amount'), 2, '.', ''),
			'discountAmount' => 100 * abs(number_format($order->getDiscountAmount(), 2, '.', '')),
			'currency' => $order->getOrderCurrencyCode(),
			'orderNumber' => $order->getIncrementId(),
			'connectionid' => Mage::getStoreConfig('id_activecampaign/config/connection_id'),
			'customerid' => Mage::helper('id_activecampaign/api')->customerExists( $order->getCustomerEmail() )
		);

		return $payload;
	}

	/**
	 * Prepares a payload array for a given quote
	 * @param  object $quote The quote object
	 * @return array         Payload data
	 */
	public function createAbandonedCartPayload($quote)
	{
		$quote_products = array();
		foreach( $quote->getAllVisibleItems() as $item ) {
			if($item->getProductType() == 'configurable') {
				$product = $item->getProduct();
			} else {
				$product = Mage::getModel('catalog/product')->setStoreId($item->getQuote()->getStoreId())->load($item->getProductId());
			}
			$quote_products[] = array(
				'externalid' => $item->product_id,
				'name' => $item->name,
				'price' => 100 * number_format($item->price_incl_tax, 2, '.', ''),
				'quantity' => intval( $item->getQty() ),
				'category' => "",
				'sku' => $item->sku,
				'description' => $item->description,
				'imageUrl' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage(),
				'productUrl' => "",
			);
		}

		$payload = array(
			'externalcheckoutid' => $quote->getId(),
			'source' => "1",
			'email' => $quote->getCustomerEmail(),
			'orderProducts' => $quote_products,
			'shippingMethod' => $quote->getShippingAddress()->getShippingDescription(),
			'totalPrice' =>  100 * number_format($quote->getGrandTotal(), 2, '.', ''),
			'externalCreatedDate' => $quote->getCreatedAt(),
			'shippingAmount' => 100 * number_format($quote->getShippingAmount(), 2, '.', ''),
			'taxAmount' => 100 * number_format($quote->getBillingAddress()->getData('tax_amount'), 2, '.', ''),
			'discountAmount' => 100 * abs(number_format($quote->getDiscountAmount(), 2, '.', '')),
			'currency' => $quote->getQuoteCurrencyCode(),
			'orderNumber' => $quote->getId(),
			'connectionid' => Mage::getStoreConfig('id_activecampaign/config/connection_id'),
			'customerid' => Mage::helper('id_activecampaign/api')->customerExists( $quote->getCustomerEmail() )
		);

		return $payload;
	}

	/**
	 * Creates a contact payload array based on a given order
	 * @param  object $order The order object
	 * @return array         Payload data
	 */
	public function createContactPayloadFromOrder($order)
	{
		return array(
			'email' => $order->getCustomerEmail(),
			'firstName' => $order->getBillingAddress()->getFirstname(),
			'lastName' => $order->getBillingAddress()->getLastname(),
			'phone' => $order->getBillingAddress()->getTelephone()
		);
	}

	/**
	 * Creates a contact payload array based on a given subscriber
	 * @param  object $subscriber The subscriber object
	 * @return array              Payload data
	 */
	public function createContactPayloadFromSubscriber($subscriber)
	{
		return array(
			'email' => $subscriber->getSubscriberEmail(),
		);
	}
}