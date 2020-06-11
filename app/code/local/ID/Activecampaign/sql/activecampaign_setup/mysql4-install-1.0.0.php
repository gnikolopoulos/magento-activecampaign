<?php

$installer = $this;
$installer->startSetup();

$installer->addAttribute("order", "activecampaign_sync", array("type"=>"int"));
$installer->addAttribute("quote", "activecampaign_sync", array("type"=>"int"));

$entityTypeId     = $installer->getEntityTypeId('customer');
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$installer->addAttribute("customer", "activecampaign_sync",  array(
	"type"     => "varchar",
	"backend"  => "",
	"label"    => "ActiveCampaign Sync",
	"input"    => "text",
	"source"   => "",
	"visible"  => true,
	"required" => false,
	"default" => "",
	"frontend" => "",
	"unique"     => false,
	"note"       => "ActiveCampaign Sync"
));

$attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "activecampaign_sync");

$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'activecampaign_sync',
	'999'  //sort_order
);

$used_in_forms=array();

$used_in_forms[] = "adminhtml_customer";
$attribute->setData("used_in_forms", $used_in_forms)
	->setData("is_used_for_customer_segment", true)
	->setData("is_system", 0)
	->setData("is_user_defined", 1)
	->setData("is_visible", 1)
	->setData("sort_order", 100);
$attribute->save();


$installer->endSetup();