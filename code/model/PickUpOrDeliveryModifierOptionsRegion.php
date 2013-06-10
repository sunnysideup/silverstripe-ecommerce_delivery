<?php

/**
 *@author nicolaas [at] sunnysideup.co.nz
 *
 **/

class PickUpOrDeliveryModifierOptionsRegion extends DataExtension {

	static $belongs_many_many = array(
		"AvailableInRegions" => "PickUpOrDeliveryModifierOptions"
	);

}

