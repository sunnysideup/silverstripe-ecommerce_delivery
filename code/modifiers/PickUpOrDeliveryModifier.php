<?php

/**
 * @author Nicolaas [at] sunnysideup.co.nz
 * @package: ecommerce
 * @sub-package: ecommerce_delivery
 * @description: Shipping calculation scheme based on SimpleShippingModifier.
 * It lets you set fixed shipping costs, or a fixed
 * cost for each region you're delivering to.
 */
class PickUpOrDeliveryModifier extends OrderModifier {

// ######################################## *** model defining static variables (e.g. $db, $has_one)

	public static $db = array(
		"TotalWeight" => "Double",
		"RegionAndCountry" => "Varchar",
		"SerializedCalculationObject" => "Text",
		"DebugString" => "HTMLText",
		"SubTotalAmount" => "Currency"
	);

	public static $has_one = array(
		"Option" => "PickUpOrDeliveryModifierOptions"
	);

	public static $singular_name = "Pickup / Delivery Charge";
		function i18n_singular_name() { return _t("PickUpOrDeliveryModifier.DELIVERYCHARGE", "Delivery / Pick-up Charge");}

	public static $plural_name = "Pickup / Delivery Charges";
		function i18n_plural_name() { return _t("PickUpOrDeliveryModifier.DELIVERYCHARGES", "Delivery / Pick-up Charges");}

	protected static $include_form_in_order_table = true;
		static function set_include_form_in_order_table($b) {self::$include_form_in_order_table = $b;}
		static function get_include_form_in_order_table() {return self::$include_form_in_order_table;}


// ######################################## *** cms variables + functions (e.g. getCMSFields, $searchableFields)

	function getCMSFields() {
		$fields = parent::getCMSFields();
		//debug fields
		$fields->removeByName("TotalWeight");
		$fields->addFieldToTab("Root.Debug", new ReadonlyField("TotalWeightShown", "total weight used for calculation", $this->TotalWeight));
		$fields->removeByName("SubTotalAmount");
		$fields->addFieldToTab("Root.Debug", new ReadonlyField("SubTotalAmountShown", "sub-total amount used for calculation", $this->SubTotalAmount));
		$fields->removeByName("SerializedCalculationObject");
		$fields->addFieldToTab("Root.Debug", new ReadonlyField("SerializedCalculationObjectShown", "debug data", unserialize($this->SerializedCalculationObject)));
		$fields->removeByName("DebugString");
		$fields->addFieldToTab("Root.Debug", new ReadonlyField("DebugStringShown", "steps taken", $this->DebugString));
		return $fields;
	}


// ######################################## *** other (non) static variables (e.g. protected static $special_name_for_something, protected $order)

	/**
	 *@var String $weight_field - the field used in the Buyable to work out the weight.
	 *
	 */
	protected static $weight_field = 'Weight';
		static function set_weight_field($s) {self::$weight_field = $s;}
		static function get_weight_field() {return self::$weight_field;}

	/**
	 * @var Float $total_weight
	 * the total amount of weight for the order
	 * saved here for speed's sake
	 */
	protected static $total_weight = null;

	/**
	 * @var DataObjectSet
	 */
	protected static $available_options = null;

	/**
	 * @var PickUpOrDeliveryModifierOptions
	 * The most applicable option
	 */
	protected static $selected_option = null;

	/**
	 * @var Double
	 * the total amount charged in the end.
	 * saved here for speed's sake
	 */
	protected static $actual_charges = 0;

	/**
	 * @var Boolean
	 * the total amount charged in the end
	 * saved here for speed's sake
	 */
	protected static $calculations_done = false;

	/**
	 * @var String
	 * Debugging tool
	 */
	protected $debugMessage = "";

// ######################################## *** CRUD functions (e.g. canEdit)

// ######################################## *** init and update functions

	/**
	 * set the selected option (selected by user using form)
	 * @param Int $optionID
	 */
	public  function setOption($optionID) {
		$optionID = intval($optionID);
		$this->OptionID = $optionID;
		$this->write();
	}

	/**
	 * updates database fields
	 * @param Bool $force - run it, even if it has run already
	 * @return void
	 */
	public function runUpdate($force = true) {
		if (isset($_GET['debug_profile'])) Profiler::mark('PickupOrDeliveryModifier::runUpdate');
		$this->debugMessage = "";
		self::$calculations_done = false;
		self::$selected_option = null;
		self::$available_options = null;
		$this->checkField("OptionID");
		$this->checkField("SerializedCalculationObject");
		$this->checkField("TotalWeight");
		$this->checkField("SubTotalAmount");
		$this->checkField("RegionAndCountry");
		$this->checkField("CalculatedTotal");
		$this->checkField("DebugString");
		if (isset($_GET['debug_profile'])) Profiler::unmark('PickupOrDeliveryModifier::runUpdate');
		parent::runUpdate($force);
	}

// ######################################## *** form functions (e. g. Showform and getform)



	/**
	 * standard Modifier Method
	 * @return Boolean
	 */
	public function ShowForm() {
		if($this->ShowInTable()) {
			if($this->Order()->Items() ) {
				if($options = $this->liveOptions()) {
					return $options->count() > 1;
				}
			}
		}
		return false;
	}

	/**
	 * Should the form be included in the editable form
	 * on the checkout page?
	 * @return Boolean
	 */
	public function ShowFormInEditableOrderTable() {
		return ($this->ShowForm() && self::$include_form_in_order_table) ? true : false;
	}

	/**
	 *
	 * @return Form
	 */
	function getModifierForm($optionalController = null, $optionalValidator = null) {
		Requirements::themedCSS("PickUpOrDeliveryModifier");
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		//Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
		//Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
		Requirements::javascript(THIRDPARTY_DIR."/jquery-form/jquery.form.js");
		Requirements::javascript("ecommerce_delivery/javascript/PickUpOrDeliveryModifier.js");
		$array = PickUpOrDeliveryModifierOptions::get_all_as_country_array();
		if($array && is_array($array) && count($array)) {
			$js = '';
			foreach($array as $key => $option) {
				if($option && is_array($option) && count($option)) {
					$js .= 'PickUpOrDeliveryModifier.addAvailableCountriesItem("'.$key.'",new Array("'.implode('","', $option).'")); ';
				}
			}
			if($js) {
				Requirements::customScript($js, "PickupOrDeliveryModifier");
			}
		}
		$fields = new FieldSet();
		$fields->push($this->headingField());
		$fields->push($this->descriptionField());
		$options = $this->liveOptions()->map('ID', 'Name');//$this->getOptionListForDropDown();
		$optionID = $this->LiveOptionID();
		$fields->push(new DropdownField('PickupOrDeliveryType', 'Preference', $options, $optionID));
		$actions = new FieldSet(
			new FormAction_WithoutLabel('processOrderModifier', 'Update Pickup / Delivery Option')
		);
		return new PickUpOrDeliveryModifier_Form($optionalController, 'PickUpOrDeliveryModifier', $fields, $actions, $optionalValidator);
	}

// ######################################## *** template functions (e.g. ShowInTable, TableTitle, etc...) ... USES DB VALUES

	/**
	 * @return Boolean
	 */
	public function ShowInTable() {
		return true;
	}

	/**
	 * @return Boolean
	 */
	public function CanBeRemoved() {
		return false;
	}

	/**
	 * NOTE: the function below is  HACK and needs fixing proper.
	 *
	 */
	public function CartValue() {return $this->getCartValue();}
	public function getCartValue() {
		return $this->LiveCalculatedTotal();
	}

// ######################################## ***  inner calculations.... USES CALCULATED VALUES

	/**
	 * returns the current selected option as object
	 * @return PickUpOrDeliveryModifierOptions;
	 */
	protected function liveOptionObject() {
		return DataObject::get_by_id('PickUpOrDeliveryModifierOptions', $this->LiveOptionID());
	}

	/**
	 * works out if Weight is applicable at all
	 * @return Boolean
	 */
	protected function useWeight(){
		return EcommerceDBConfig::current_ecommerce_db_config()->ProductsHaveWeight;
	}

	/**
	 * Returns the available delivery options based on the current country and region
	 * for the order.
	 * Must always return something!
	 * @return DataObjectSet
	 */
	protected function liveOptions() {
		if(!self::$available_options) {
			$countryID = EcommerceCountry::get_country_id();
			$regionID = EcommerceRegion::get_region_id();
			$weight = $this->LiveTotalWeight();
			$options = DataObject::get('PickUpOrDeliveryModifierOptions');
			if($options) {
				foreach($options as $option) {
					//check countries
					if($countryID) {
						$optionCountries = $option->AvailableInCountries();
						//exclude if not found in country list
						if($optionCountries->Count() > 0 && ! $optionCountries->find('ID', $countryID)) {
							continue;
						}
					}
					//check regions
					if($regionID) {
						$optionRegions = $option->AvailableInRegions();
						//exclude if not found in region list
						if($optionRegions->Count() > 0 && ! $optionRegions->find('ID', $regionID)) {
							continue;
						}
					}
					$result[] = $option;
				}
			}
			if(! isset($result)) {
				$result[] = PickUpOrDeliveryModifierOptions::default_object();
			}
			self::$available_options = new DataObjectSet($result);
		}
		return self::$available_options;
	}


// ######################################## *** calculate database fields: protected function Live[field name]  ... USES CALCULATED VALUES

	/**
	 * Precondition : There are always options available.
	 * @return Int
	 */
	protected function LiveOptionID() {
		if(!self::$selected_option) {
			$options = $this->liveOptions();
			if(self::$selected_option = $options->find('ID', $this->OptionID)) {
				//do nothing;
			}
			else {
				self::$selected_option = $options->find('IsDefault', 1);
				if(! self::$selected_option) {
					self::$selected_option = $options->First();
				}
			}
		}
		return self::$selected_option->ID;
	}

	/**
	 * @return String
	 */
	protected function LiveName() {
		$obj = $this->liveOptionObject();
		if(is_object($obj)) {
			$v = $obj->Name;
			if($obj->ExplanationPageID) {
				$page = $obj->ExplanationPage();
				if($page) {
					$v .= '<div id="PickUpOrDeliveryModifierExplanationLink"><a href="'.$page->Link().'" class="externalLink">'.$page->Title.'</a></div>';
				}
			}
			return $v;
		}
		return _t("PickUpOrDeliveryModifier.POSTAGEANDHANDLING", "Postage and Handling");
	}

	/**
	 * cached in Order, no need to cache here.
	 * @return Double
	 */
	protected function LiveSubTotalAmount() {
		$order = $this->Order();
		return $order->SubTotal();
	}

	/**
	 * description of region and country being shipped to.
	 * @return PickUpOrDeliveryModifierOptions | NULL
	 */
	protected function LiveSerializedCalculationObject() {
		$obj = $this->liveOptionObject();
		if($obj) {
			return serialize($obj);
		}
	}

	/**
	 * description of region and country being shipped to.
	 * @return String
	 */
	protected function LiveRegionAndCountry() {
		$details = array();
		$option = $this->Option();
		if($option) {
			$regionID = EcommerceRegion::get_region_id();
			if($regionID) {
				$region = DataObject::get_by_id("EcommerceRegion", $regionID);
				if($region) {
					$details[] = $region->Name;
				}
			}
			$countryID = EcommerceCountry::get_country_id();
			if($countryID) {
				$country = DataObject::get_by_id("EcommerceCountry", $countryID);
				if($country) {
					$details[] = $country->Name;
				}
			}
		}
		else {
			return _t("PickUpOrDeliveryModifier.NOTSELECTED", "No delivery option has been selected");
		}
		if(count($details)) {
			return implode(", ", $details);
		}
	}

	/**
	* @return Double
	**/
	protected function LiveCalculatedTotal() {
		//________________ start caching mechanism
		if(self::$calculations_done) {
			return self::$actual_charges;
		}
		self::$calculations_done = true;
		//________________ end caching mechanism

		self::$actual_charges = 0;
		//do we have enough information
		$obj = $this->liveOptionObject();
		if($items = $this->Order()->Items() && is_object($obj) && $obj->exists()) {
			$this->debugMessage .= "<hr />option selected: ".$obj->Title.", and items present";
			//lets check sub-total
			$subTotalAmount = $this->LiveSubTotalAmount();
			$this->debugMessage .= "<hr />sub total amount is: \$". $subTotalAmount;
			// no need to charge, order is big enough
			$minForZeroRate = floatval($obj->MinimumOrderAmountForZeroRate);
			if($minForZeroRate > 0 && $minForZeroRate < $subTotalAmount) {
				self::$actual_charges =  0;
				$this->debugMessage .= "<hr />Minimum Order Amount For Zero Rate: ".$obj->MinimumOrderAmountForZeroRate." is lower than amount ".self::$actual_charges;
			}
			else {
				$weight = $this->LiveTotalWeight();
				if($weight) {
					$this->debugMessage .= "<hr />there is weight: {$weight}gr.";
					//weight brackets
					$weightBrackets = $obj->WeightBrackets();
					$foundWeightBracket = null;
					$weightBracketQuantity = 1;
					$additionalWeightBracket = null;
					if($weightBrackets && $weightBrackets->Count()) {
						$minimumMinimum = null;
						$maximumMaximum = null;
						foreach($weightBrackets as $weightBracket) {
							if((!$foundWeightBracket) && ($weightBracket->MinimumWeight <= $weight) && ($weight <= $weightBracket->MaximumWeight)) {
								$foundWeightBracket = $weightBracket;
							}
							//look for absolute min and max
							if($minimumMinimum == null || ($weightBracket->MinimumWeight > $minimumMinimum->MinimumWeight)) {
								$minimumMinimum = $weightBracket;
							}
							if($maximumMaximum == null || ($weightBracket->MaximumWeight > $maximumMaximum->MaximumWeight)) {
								$maximumMaximum = $weightBracket;
							}
						}
						if(!$foundWeightBracket) {
							if($weight < $minimumMinimum->MinimumWeight) {
								$foundWeightBracket = $minimumMinimum;
							}
							elseif($weight > $maximumMaximum->MaximumWeight) {
								$foundWeightBracket = $maximumMaximum;
								$weightBracketQuantity = floor($weight / $maximumMaximum->MaximumWeight);
								$restWeight = $weight - ($maximumMaximum->MaximumWeight * $weightBracketQuantity);
								$additionalWeightBracket = null;
								foreach($weightBrackets as $weightBracket) {
									if(($weightBracket->MinimumWeight <= $restWeight) && ($restWeight <= $weightBracket->MaximumWeight)) {
										$additionalWeightBracket = $weightBracket;
										break;
									}
								}
							}
						}
					}
					//we found some applicable weight brackets
					if($foundWeightBracket) {
						self::$actual_charges += $foundWeightBracket->FixedCost * $weightBracketQuantity;
						$this->debugMessage .= "<hr />found Weight Bracket (from {$foundWeightBracket->MinimumWeight}gr. to {$foundWeightBracket->MaximumWeight}gr.): \${$foundWeightBracket->FixedCost} ({$foundWeightBracket->Name}) from  times $weightBracketQuantity";
						if($additionalWeightBracket) {
							self::$actual_charges += $additionalWeightBracket->FixedCost;
							$this->debugMessage .= "<hr />+ additional Weight Bracket (from {$additionalWeightBracket->MinimumWeight}gr. to {$additionalWeightBracket->MaximumWeight}gr.): \${$additionalWeightBracket->FixedCost} ({$foundWeightBracket->Name})";
						}
					}
					elseif($weight && $obj->WeightMultiplier) {
						// add weight based shipping
						if(!$obj->WeightUnit) {
							$obj->WeightUnit = 1;
						}
						$this->debugMessage .= "<hr />actual weight:".$weight." multiplier = ".$obj->WeightMultiplier." weight unit = ".$obj->WeightUnit." ";
						//legacy fix
						$units = ceil($weight / $obj->WeightUnit);
						$weightCharge =  $units * $obj->WeightMultiplier;
						self::$actual_charges += $weightCharge;
						$this->debugMessage .= "<hr />weight charge: ".$weightCharge;
					}
				}
					// add percentage
				if($obj->Percentage) {
					$percentageCharge = $subTotalAmount * $obj->Percentage;
					self::$actual_charges += $percentageCharge;
					$this->debugMessage .= "<hr />percentage charge: \$".$percentageCharge;
				}
				// add fixed price
				if($obj->FixedCost <> 0) {
					self::$actual_charges += $obj->FixedCost;
					$this->debugMessage .= "<hr />fixed charge: \$". $obj->FixedCost;
				}
				//is it enough?
				if(self::$actual_charges < $obj->MinimumDeliveryCharge && $obj->MinimumDeliveryCharge > 0) {
					$oldActualCharge = self::$actual_charges;
					self::$actual_charges = $obj->MinimumDeliveryCharge;
					$this->debugMessage .= "<hr />too little: actual charge: ".$oldActualCharge.", minimum delivery charge: ".$obj->MinimumDeliveryCharge;
				}
				// is it too much
				if(self::$actual_charges > $obj->MaximumDeliveryCharge  && $obj->MaximumDeliveryCharge > 0) {
					self::$actual_charges = $obj->MaximumDeliveryCharge;
					$this->debugMessage .= "<hr />too much: ".self::$actual_charges.", maximum delivery charge is ".$obj->MaximumDeliveryCharge;
				}
			}
		}
		else {
			if(!$items) {
				$this->debugMessage .= "<hr />no items present";
			}
			else {
				$this->debugMessage .= "<hr />no delivery option available";
			}
		}
		$this->debugMessage .= "<hr />final score: \$".self::$actual_charges;
		//special case, we are using weight and there is no weight!
		return self::$actual_charges;
	}

	/**
	 *
	 *
	 * @return Double
	 */
	protected function LiveTotalWeight() {
		if(self::$total_weight === null) {
			self::$total_weight = 0;
			if($this->useWeight()) {
				if($fieldName = self::get_weight_field()) {
					$items = $this->Order()->Items();
					//get index numbers for bonus products - this can only be done now once they have actually been added
					if($items && $items->count()) {
						foreach($items as $itemIndex => $item) {
							$buyable = $item->Buyable();
							if($buyable) {
								// Calculate the total weight of the order
								if(! empty($buyable->$fieldName) && $item->Quantity) {
									self::$total_weight += $buyable->$fieldName * $item->Quantity;
								}
							}
						}
					}
				}
			}
		}
		return self::$total_weight;
	}

	/**
	 * returns an explanation of cost.
	 * @return String
	 */
	protected function LiveDebugString() {
		return $this->debugMessage;
	}


// ######################################## *** Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)

	public function IsChargeable () {
		return true;
	}

// ######################################## *** standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)

	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		// we must check for individual database types here because each deals with schema in a none standard way
		$db = DB::getConn();
		if( $db instanceof PostgreSQLDatabase ){
      $exist = DB::query("SELECT column_name FROM information_schema.columns WHERE table_name ='PickUpOrDeliveryModifier' AND column_name = 'PickupOrDeliveryType'")->numRecords();
		}
		else{
			// default is MySQL - broken for others, each database conn type supported must be checked for!
      $exist = DB::query("SHOW COLUMNS FROM \"PickUpOrDeliveryModifier\" LIKE 'PickupOrDeliveryType'")->numRecords();
		}
 		if($exist > 0) {
 			if($modifiers = DataObject::get('PickUpOrDeliveryModifier')) {
				$defaultOption = DataObject::get_one("PickUpOrDeliveryModifierOptions", "\"IsDefault\" = 1");
				foreach($modifiers as $modifier) {
					if(!isset($modifier->OptionID) || !$modifier->OptionID) {
						$option = DataObject::get_one("PickUpOrDeliveryModifierOptions", "\"Code\" = '".$modifier->Code."'");
						if(!$option) {
							$option = $defaultOption;
						}
						$modifier->OptionID = $option->ID;
						// USING QUERY TO UPDATE
						DB::query("UPDATE \"PickUpOrDeliveryModifier\" SET \"OptionID\" = ".$option->ID." WHERE \"PickUpOrDeliveryModifier\".\"ID\" = ".$modifier->ID);
						DB::alteration_message('Updated modifier from code to option ID', 'edited');
					}
				}
			}
		}
	}

// ######################################## *** AJAX related functions
	/**
	 *
	 * @param Array $js javascript array
	 * @return Array for AJAX JSON
	 **/
	function updateForAjax(array &$js) {
		parent::updateForAjax($js);
		$options = $this->LiveOptions()->map('ID', 'Name');
		foreach($options as $id => $name) {
			$jsonOptions[] = array('id' => $id, 'name' => $name);
		}
		$js[] = array(
			't' => 'dropdown',
			's' => 'PickupOrDeliveryType',
			'p' => $this->LiveOptionID(),
			'v' => $jsonOptions
		);
	}

// ######################################## *** debug functions

}

class PickUpOrDeliveryModifier_Form extends OrderModifierForm {

	function processOrderModifier($data, $form = null) {
		if(isset($data['PickupOrDeliveryType'])) {
			$newOption = intval($data['PickupOrDeliveryType']);
			if(DataObject::get_by_id("PickUpOrDeliveryModifierOptions", $newOption)) {
				$order = ShoppingCart::current_order();
				if($order) {
					if($modifiers = $order->Modifiers("PickUpOrDeliveryModifier")) {
						foreach($modifiers as $modifier) {
							$modifier->setOption($newOption);
							$modifier->runUpdate();
						}
						return ShoppingCart::singleton()->setMessageAndReturn(_t("PickUpOrDeliveryModifier.UPDATED", "Delivery option updated"), "good");
					}
				}
			}
		}
		return ShoppingCart::singleton()->setMessageAndReturn( _t("PickUpOrDeliveryModifier.UPDATED", "Delivery option could NOT be updated"), "bad");
	}

}
