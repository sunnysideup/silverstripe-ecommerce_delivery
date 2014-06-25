<?php

class EcommerceTaskUpgradePickUpOrDeliveryModifier extends BuildTask {

	protected $title = "Upgrade PickUpOrDeliveryModifier";

	protected $description = "Fix the option field";

	function run($request){
		$db = DB::getConn();
		if( $db instanceof PostgreSQLDatabase ){
      $exist = DB::query("SELECT column_name FROM information_schema.columns WHERE table_name ='PickUpOrDeliveryModifier' AND column_name = 'PickupOrDeliveryType'")->numRecords();
		}
		else{
			// default is MySQL - broken for others, each database conn type supported must be checked for!
      $exist = DB::query("SHOW COLUMNS FROM \"PickUpOrDeliveryModifier\" LIKE 'PickupOrDeliveryType'")->numRecords();
		}
 		if($exist > 0) {
			$modifiers = PickUpOrDeliveryModifier::get()->filter(array("OptionID" => 0));
 			if($modifiers->count()) {
				$defaultOption = PickUpOrDeliveryModifierOptions::get()->filter(array("IsDefault" => 1))->First();
				foreach($modifiers as $modifier) {
					if(!isset($modifier->OptionID) || !$modifier->OptionID) {
						$option = PickUpOrDeliveryModifierOptions::get()->filter(array("Code" => $modifier->Code))->First();
						if(!$option) {
							$option = $defaultOption;
						}
						// USING QUERY TO UPDATE
						DB::query("UPDATE \"PickUpOrDeliveryModifier\" SET \"OptionID\" = ".$option->ID." WHERE \"PickUpOrDeliveryModifier\".\"ID\" = ".$modifier->ID);
						DB::alteration_message('Updated modifier from code to option ID', 'edited');
					}
				}
			}
		}
	}

}
