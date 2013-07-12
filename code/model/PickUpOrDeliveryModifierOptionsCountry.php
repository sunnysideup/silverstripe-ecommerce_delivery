<?php

/**
 *@author nicolaas [at] sunnysideup.co.nz
 *
 **/

class PickUpOrDeliveryModifierOptionsCountry extends DataExtension {

	private static $belongs_many_many = array(
		"AvailableInCountries" => "PickUpOrDeliveryModifierOptions"
	);

}

