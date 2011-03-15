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
		"SerializedCalculationObject" => "Text",
		'DebugString' => 'HTMLText',
		'SubTotalAmount' => 'Currency'
	);

	public static $has_one = array(
		"Option" => "PickUpOrDeliveryModifierOptions"
	);

	public static $defaults = array("Type" => "Chargeable");

// ######################################## *** cms variables + functions (e.g. getCMSFields, $searchableFields)

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("CountryCode", new DropDownField("CountryCode", self::$field_labels["CountryCode"], Geoip::getCountryDropDown()));
		return $fields;
	}

	public static $singular_name = "Pickup / Delivery Charge";

	public static $plural_name = "Pickup / Delivery Charges";

// ######################################## *** other (non) static variables (e.g. protected static $special_name_for_something, protected $order)

	protected static $weight_field = "";
		static function set_weight_field($s) {self::$weight_field = $s;}
		static function get_weight_field() {return self::$weight_field;}

	protected static $total_weight = null;

	protected static $actual_charges = 0;

	protected static $calculations_done = false;

	protected static $form_header = 'Pick-up / Deliver';
		static function set_form_header($v) {self::$form_header = $v;}

	protected $debugMessage = "";

// ######################################## *** CRUD functions (e.g. canEdit)
// ######################################## *** init and update functions



	public  function setOption($optionID) {
		$optionID = intval($optionID);
		$this->OptionID = $optionID;
		$this->write();
	}

	public function runUpdate() {
		$this->checkField("TotalWeight");
		$this->checkField("DebugString");
		$this->checkField("SubTotalAmount");
		$this->checkField("OptionID");
		parent::runUpdate();
	}



// ######################################## *** form functions (e. g. showform and getform)


	public function showForm() {
		return $this->Order()->Items();
	}

	function getForm($controller) {
		Requirements::themedCSS("PickUpOrDeliveryModifier");
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		Requirements::javascript(THIRDPARTY_DIR."/jquery-form/jquery.form.js");
		Requirements::javascript("ecommerce_modifiers/javascript/PickUpOrDeliveryModifier.js");
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
		$options = $this->getOptionListForDropDown();
		$fields->push(new HeaderField('PickupOrDeliveryTypeHeader', self::$form_header));
		$defaultOptionID = $this->LiveOptionID();
		$fields->push(new DropdownField('PickupOrDeliveryType','Preference',$options, $defaultOptionID));
		$validator = null;
		$actions = new FieldSet(
			new FormAction_WithoutLabel('processOrderModifier', 'Update Pickup / Delivery Option')
		);
		$controller = new PickUpOrDeliveryModifier_AjaxController();
		return new PickUpOrDeliveryModifier_Form($controller, 'ModifierForm', $fields, $actions, $validator);
	}


	protected function getOptionListForDropDown() {
		$array = array();
		$options = DataObject::get("PickUpOrDeliveryModifierOptions");
		if($options) {
			foreach($options as $option) {
				$array[$option->ID] = $option->Name;
			}
		}
		else {
			$array[0] = _t("PickUpOrDeliveryModifier.NOOPTIONSAVAILABLE", "No pick-up or delivery options available");
		}
		return $array;
	}

// ######################################## *** template functions (e.g. ShowInTable, TableTitle, etc...) ... USES DB VALUES

	public function ShowInTable() {
		return true;
	}
	public function CanRemove() {
		return false;
	}
	public function TableValue() {
		return $this->Amount;
	}
	public function TableTitle() {
		return $this->Name;
	}

// ######################################## ***  inner calculations.... USES CALCULATED VALUES


	protected function LiveOptionObject() {
		return DataObject::get_by_id("PickUpOrDeliveryModifierOptions", $this->LiveOptionID());
	}

// ######################################## *** calculate database fields: protected function Live[field name]  ... USES CALCULATED VALUES


	/**
	*@return int
	**/

	protected function LiveOptionID() {
		$optionID = $this->OptionID;
		if(!$optionID) {
			$option = PickUpOrDeliveryModifierOptions::default_object();
			$optionID = $option->ID;
		}
		return $optionID;
	}

	/**
	*@return string
	**/


	protected function LiveName() {
		$start =  microtime();
		$obj = $this->LiveOptionObject();
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

	protected function LiveSubTotalAmount() {
		$order = $this->Order();
		return $order->SubTotal();
	}

	/**
	*@return currency
	**/

	protected function LiveAmount() {
		$amount = 0;
		$obj = $this->LiveOptionObject();
		self::$actual_charges = 0;
		if($items = $this->Order()->Items()) {
			$amount = $this->LiveSubTotalAmount();
			if(($amount-0) == 0){
				self::$actual_charges = 0;
				$this->debugMessage .= "<hr />sub total amount is 0";
			}
			else {
				if( is_object($obj) && $obj->exists()) {
					// no need to charge, order is big enough
					$this->debugMessage .= "<hr />option selected ".$obj->Title;
					$minForZeroRate = floatval($obj->MinimumOrderAmountForZeroRate);
					if($minForZeroRate > 0 && $minForZeroRate < $amount) {
						self::$actual_charges =  0;
						$this->debugMessage .= "<hr />MinimumOrderAmountForZeroRate: ".$obj->MinimumOrderAmountForZeroRate." is lower than amount ".self::$actual_charges;
					}
					else {
						// add weight based shipping
						$weight = $this->LiveTotalWeight();
						$this->debugMessage .= "<hr />actual weight:".$weight." multiplier = ".$obj->WeightMultiplier." weight unit = ".$obj->WeightUnit." ";
						//legacy fix
						if(!$obj->WeightUnit) { $obj->WeightUnit = 1;}
						if($weight && $obj->WeightMultiplier && $obj->WeightUnit ) {
							$units = ceil($weight / $obj->WeightUnit);
							self::$actual_charges += $units * $obj->WeightMultiplier;
							$this->debugMessage .= "<hr />weight charge: ".self::$actual_charges;
						}
						// add percentage
						if($obj->Percentage) {
							self::$actual_charges += $amount * $obj->Percentage;
							$this->debugMessage .= "<hr />percentage charge: ".$amount * $obj->Percentage;
						}
						// add fixed price
						if($obj->FixedCost) {
							self::$actual_charges += $obj->FixedCost;
							$this->debugMessage .= "<hr />fixed charge: ". $obj->FixedCost;
						}
						//is it enough?
						if(self::$actual_charges < $obj->MinimumDeliveryCharge) {
							$oldActualCharge = self::$actual_charges;
							self::$actual_charges = $obj->MinimumDeliveryCharge;
							$this->debugMessage .= "<hr />too little: actual charge: ".$oldActualCharge.", minimum delivery charge: ".$obj->MinimumDeliveryCharge;
						}
						// is it too much
						if(self::$actual_charges > $obj->MaximumDeliveryCharge) {
							self::$actual_charges = $obj->MaximumDeliveryCharge;
							$this->debugMessage .= "<hr />too much".self::$actual_charges;
						}
					}
				}
				else {
					//do nothing
					$this->debugMessage .= "<hr />default";
				}
			}
		}
		else {
			self::$actual_charges = 0;
			$this->debugMessage .= "<hr />no action";
		}
		$this->debugMessage .= "<hr />final score: ".self::$actual_charges;
		if(isset($_GET["debug"])) {
			print_r($this->debugMessage);
		}
		return self::$actual_charges;
	}


	protected function LiveTotalWeight() {
		if(self::get_weight_field()) {
			if(self::$total_weight === null) {
				$items = ShoppingCart::get_items();
				//get index numbers for bonus products - this can only be done now once they have actually been added
				if($items) {
					foreach($items as $itemIndex => $item) {
						if($product = $item->Buyable()) {
							$fieldName = self::get_weight_field();
						// Calculate the total weight of the order
							if(!empty($product->$fieldName) && $item->Quantity) {
								self::$total_weight += intva($product->$fieldName) * $item->Quantity;
							}
							elseif(!$product->Weight)  {
								$this->debugMessage .= "<hr />product without weight: ".$product->Weight;
							}
							elseif(!$item->Quantity) {
								$this->debugMessage .= "<hr />item without uc quanty: ".$item->Quantity;
								if($this->quanty) {
									$this->debugMessage .= "<hr />item does have lc quanty: ".$item->quanty;
								}
							}
						}
					}
				}
			}
		}
		return self::$total_weight;
	}

	protected function LiveDebugString() {
		return $this->debugMessage;
	}


// ######################################## *** Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)

	protected function IsChargeable () {
		return true;
	}

// ######################################## *** standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)

	function onBeforeWrite() {
		parent::onBeforeWrite();
	}

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
					if(!$modifier->OptionID) {
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
// ######################################## *** debug functions

}

class PickUpOrDeliveryModifier_Form extends OrderModifierForm {

	public function processOrderModifier($data, $form) {
		$order = ShoppingCart::current_order();
		$modifiers = $order->Modifiers();
		foreach($modifiers as $modifier) {
			if (get_class($modifier) == 'PickUpOrDeliveryModifier') {
				if(isset($data['PickupOrDeliveryType'])) {
					$modifier->setOption($data['PickupOrDeliveryType']);
				}
			}
		}
		Order::save_current_order();
		if(Director::is_ajax()) {
			return ShoppingCart_Controller::json_code();
		}
		else {
			Director::redirect(CheckoutPage::find_link());
		}
		return;
	}
}

class PickUpOrDeliveryModifier_AjaxController extends Controller {

	function ModifierForm($request) {
		if(isset($request['PickupOrDeliveryType'])) {
			$newOption = intval($request['PickupOrDeliveryType']);
			if(DataObject::get_by_id("PickUpOrDeliveryModifierOptions", $newOption)) {
				$order = ShoppingCart::current_order();
				$modifiers = $order->Modifiers();
				foreach($modifiers as $modifier) {
					if ($modifier InstanceOf PickUpOrDeliveryModifier) {
						$modifier->setOption($newOption);
						$modifier->runUpdate();
						return ShoppingCart::return_message("success", _t("PickUpOrDeliveryModifier.UPDATED", "Delivery option updated"));
					}
				}
			}
		}
		return ShoppingCart::return_message("failure", _t("PickUpOrDeliveryModifier.UPDATED", "Delivery option could NOT be updated"));
	}

	function Link() {
		return "pickupordeliverymodifier";
	}


}
