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
		$fields->replaceField("CountryCode", new DropDownField("CountryCode", self::$field_labels["CountryCode"], Geoip::getCountryDropDown()));
		return $fields;
	}
	/*
	public function ShowForm() {
		$show = parent::ShowForm();
		$options = $this->LiveOptions();
		return $show && $options->Count() > 1;
	}

	/**
	 * Returns the available delivery options based on the current order country and region settings.
	 * @return DataObjectSet

	protected function LiveOptions() {
		$countryID = EcommerceCountry::get_country_id();
		$regionID = EcommerceRegion::get_region();

		$options = DataObject::get('PickUpOrDeliveryModifierOptions');
		if($options) {
			foreach($options as $option) {

				if($countryID) {
					$optionCountries = $option->AvailableInCountries();
					if(! $optionCountries->find('ID', $countryID)) { // Invalid
						continue;
					}
				}

				if($regionID) {
					$optionRegions = $option->AvailableInRegions();
					if(! $optionRegions->find('ID', $regionID)) { // Invalid
						continue;
					}
				}

				$result[] = $option;
			}
		}

		if(! isset($result)) {
			$result[] = PickUpOrDeliveryModifierOptions::default_object();
		}
		return new DataObjectSet($result);
	}
	*/

	function TableSubTitle() {return $this->getTableSubTitle();}
	function getTableSubTitle() {
		return $this->RegionAndCountry;
	}

}
