<?php

class ID_Activecampaign_Block_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('*/activecampaign/createConnection');

        if( Mage::getStoreConfig('id_activecampaign/config/connection_id') == '' ) {
            $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setType('button')
                ->setClass('scalable')
                ->setLabel('Create Connection')
                ->setOnClick("setLocation('$url')")
                ->toHtml();
        }

        return $html;
    }
}
?>