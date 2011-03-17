<?php

/**
 *@author nicolaas [at] sunnysideup.co.nz
 *
 **/

class PickUpOrDeliveryModifierOptionsCountry extends DataObjectDecorator {

	public function extraStatics() {
		return array (
			'belongs_many_many' => array(
				"AvailableInCountries" => "PickUpOrDeliveryModifierOptions"
			)
		);
	}

}

