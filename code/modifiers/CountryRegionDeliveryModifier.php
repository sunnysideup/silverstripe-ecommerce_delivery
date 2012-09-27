<?

class CountryRegionDeliveryModifier extends PickUpOrDeliveryModifier {
	
	public function ShowForm() {
		$show = parent::ShowForm();
		$options = $this->LiveOptions();
		return $show && $options;
	}

	/**
	 * Returns the available delivery options based on the current order country and region settings.
	 * @return DataObjectSet
	 */
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

		if(isset($result)) {
			$result = new DataObjectSet($result);
		}
		else {
			PickUpOrDeliveryModifierOptions::default_object(); // Just to be sure there is always at least 1 default option
			$result = DataObject::get('PickUpOrDeliveryModifierOptions', 'IsDefault = 1');
		}

		return $result;
	}
}