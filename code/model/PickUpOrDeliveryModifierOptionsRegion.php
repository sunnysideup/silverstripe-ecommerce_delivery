<?php

/**
 *@author nicolaas [at] sunnysideup.co.nz
 *
 **/

class PickUpOrDeliveryModifierOptionsRegion extends DataExtension {

	private static $belongs_many_many = array(
		"AvailableInRegions" => "PickUpOrDeliveryModifierOptions"
	);

}

