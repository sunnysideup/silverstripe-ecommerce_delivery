<?php

/**
 *@author nicolaas [at] sunnysideup.co.nz
 *
 **/

class PickUpOrDeliveryModifierOptionsRegion extends DataObjectDecorator {

	public function extraStatics() {
		return array (
			'belongs_many_many' => array(
				"AvailableInRegions" => "PickUpOrDeliveryModifierOptions"
			)
		);
	}

}

