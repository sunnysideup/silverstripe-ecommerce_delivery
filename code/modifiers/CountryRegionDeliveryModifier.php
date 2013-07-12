<?php

/**
 * to do: delete this class!
 *
 *
 *
 *
 *
 *
 */

class CountryRegionDeliveryModifier extends PickUpOrDeliveryModifier {


// ######################################## *** cms variables + functions (e.g. getCMSFields, $searchableFields)

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("CountryCode", new DropDownField("CountryCode", self::$field_labels["CountryCode"], EcommerceCountry::get_country_dropdown()));
		return $fields;
	}

	function TableSubTitle() {return $this->getTableSubTitle();}
	function getTableSubTitle() {
		return $this->RegionAndCountry;
	}

}
