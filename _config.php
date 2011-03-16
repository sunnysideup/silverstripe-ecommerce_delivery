<?php


/**
 * developed by www.sunnysideup.co.nz
 * author: Nicolaas - modules [at] sunnysideup.co.nz
**/
Director::addRules(50, array(
	'pickupordeliverymodifier/$Action/$ID/$OtherID' => 'PickUpOrDeliveryModifier_AjaxController'
));

Object::add_extension('EcommerceCountry', 'PickUpOrDeliveryModifierOptionsCountry');

//copy the lines between the START AND END line to your /mysite/_config.php file and choose the right settings
//===================---------------- START ecommerce_delivery MODULE ----------------===================
//NOTE: add http://svn.gpmd.net/svn/open/multiselectfield/tags/0.2/ for nicer interface
//PickUpOrDeliveryModifier::set_form_header("Delivery Option (REQUIRED)");
//ProductsAndGroupsModelAdmin::add_managed_model("PickUpOrDeliveryModifierOptions");
//===================---------------- END ecommerce_delivery  MODULE ----------------===================
