<?php

class ID_Activecampaign_Model_System_Config_Source_Fields_Values
{
	public function toOptionArray()
	{
		$all_fields = Mage::helper('id_activecampaign')->getCustomFields();
		$values = array();

		if( $all_fields ) {
			foreach ($all_fields as $field) {
				$values[] = array(
					'value' => $field->id,
					'label' => $field->title,
				);
			}
		}

		return $values;
	}
}