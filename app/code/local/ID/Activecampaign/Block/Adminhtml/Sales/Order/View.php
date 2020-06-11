<?php

class ID_Activecampaign_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{
    public function __construct()
    {
        $this->_objectId    = 'order_id';
        $this->_controller  = 'sales_order';
        $this->_mode        = 'view';
        $order = $this->getOrder();

        parent::__construct();

        if( $order->getState() !== Mage_Sales_Model_Order::STATE_CANCELED ) {
            if( Mage::getStoreConfig('id_activecampaign/config/connection_id') != '' ) {
                $this->_addButton('activecampaign', array(
                    'label'     => 'Send to ActiveCampaign',
                    'class'     => 'go',
                    'onclick'   => 'setLocation(\'' . Mage::helper('adminhtml')->getUrl('*/activecampaign/activecampaign', array('order' => $order->getId())) . '\')',
                ));
            }
        }

    }
}