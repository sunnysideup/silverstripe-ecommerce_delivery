<?php

/**
 *@author nicolaas [at] sunnysideup.co.nz
 *
 **/

class PickUpOrDeliveryModifierOptionsCountry extends DataObject {

	static $db = array(
		"Code" => "Varchar(3)",
		"Name" => "Varchar(200)",
	);

	static $indexes = array(
		"Code" => true
	);

	static $default_sort = "Name";

	static $belongs_many_many = array(
		"AvailableInCountries" => "PickUpOrDeliveryModifierOptions"
	);

	public static $singular_name = "Country";

	public static $plural_name = "Countries";

	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		if(!DataObject::get("PickUpOrDeliveryModifierOptionsCountry")) {
			$array = Geoip::getCountryDropDown();
			foreach($array as $key => $value) {
				$obj = new PickUpOrDeliveryModifierOptionsCountry();
				$obj->Code = $key;
				$obj->Name = $value;
				$obj->write();
			}
		}
	}
}

