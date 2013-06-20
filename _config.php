<?php


/**
 * developed by www.sunnysideup.co.nz
 * author: Nicolaas - modules [at] sunnysideup.co.nz
**/



//copy the lines between the START AND END line to your /mysite/_config.php file and choose the right settings
//===================---------------- START ecommerce_delivery MODULE ----------------===================
//MUST SET
/**
 * ADD TO ECOMMERCE.YAML:
Order:
  modifiers:
    - PickUpOrDeliveryModifier

StoreAdmin:
  managed_models:
    - PickUpOrDeliveryModifierOptions

*/

//MAY SET
//Object::add_extension('EcommerceCountry', 'PickUpOrDeliveryModifierOptionsCountry');
//Object::add_extension('EcommerceRegion', 'PickUpOrDeliveryModifierOptionsRegion');
//PickUpOrDeliveryModifier::set_include_form_in_order_table(true);
//CountryRegionDeliveryModifier::set_check_options_count_to_show_form(false);

//NOTE: add http://svn.gpmd.net/svn/open/multiselectfield/tags/0.2/ for nicer interface
//===================---------------- END ecommerce_delivery  MODULE ----------------===================
