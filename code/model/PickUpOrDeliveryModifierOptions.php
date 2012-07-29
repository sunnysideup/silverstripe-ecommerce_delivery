<?php

/**
 *@author nicolaas [at] sunnysideup.co.nz
 *
 **/

class PickUpOrDeliveryModifierOptions extends DataObject {

	static $db = array(
		"IsDefault" => "Boolean",
		"Code" => "Varchar(25)",
		"Name" => "Varchar(175)",
		"MinimumDeliveryCharge" => "Currency",
		"MaximumDeliveryCharge" => "Currency",
		"MinimumOrderAmountForZeroRate" => "Currency",
		"WeightMultiplier" => "Double",
		"WeightUnit" => "Double",
		"Percentage" => "Double",
		"FixedCost" => "Currency",
		"Sort" => "Int"
	);

	public static $has_one = array(
		"ExplanationPage" => "SiteTree"
	);

	public static $many_many = array(
		"AvailableInCountries" => "EcommerceCountry",
		"AvailableInRegions" => "EcommerceRegion"
	);

	public static $indexes = array(
		"IsDefault" => true,
		"Code" => true
	);

	public static $searchable_fields = array(
		"Code",
		"Name" => "PartialMatchFilter"
	);

	public static $field_labels = array(
		"IsDefault" => "Default delivery option?",
		"Code" => "Code",
		"Name" => "Long Name",
		"MinimumDeliveryCharge" => "Minimum delivery charge. Enter zero (0) to ignore.",
		"MaximumDeliveryCharge" => "Maximum delivery charge. Enter zero (0) to ignore.",
		"MinimumOrderAmountForZeroRate" => "Minimum for 0 rate (i.e. if the total order is over ... then there is no fee for this option). Enter zero (0) to ignore.",
		"WeightMultiplier" => "Cost per kilogram. It multiplies the total weight of the total order with this number to work out charge for delivery. Enter zero (0) to ignore.",
		"WeightUnit" => "Weight unit in kilograms.  If you enter 0.1 here, the price will go up with every 100 grams of total order weight.  Enter zero (0) to ignore.",
		"Percentage" => "Percentage (number between 0 = 0% and 1 = 100%) of total order cost as charge for this option (e.g. 0.05 would add 5 cents to every dollar ordered).  Enter zero (0) to ignore.",
		"FixedCost" =>  "This option has a fixed cost (e.g. entering 10 will add a fixed 10 dollars delivery fee).  Enter zero (0) to ignore.",
		"Sort" =>  "Sort Index - lower numbers show first."
	);

	public static $defaults = array(
		"Code" => "homedelivery",
		"Name" => "Home Delivery",
		"MinimumDeliveryCharge" => 10,
		"MaximumDeliveryCharge" => 100,
		"MinimumOrderAmountForZeroRate" => 50,
		"WeightMultiplier" => 0,
		"WeightUnit" => 1,
		"Percentage" => 0,
		"FixedCost" => 10,
		"Sort" => 100
	);

	public static $summary_fields = array(
		"IsDefaultNice" => "Default Option",
		"Code",
		"Name"
	);

	public static $casting = array(
		"IsDefaultNice"
	);

	public static $singular_name = "Delivery / Pick-up Option";
		function i18n_singular_name() { return _t("PickUpOrDeliveryModifierOptions.DELIVERYOPTION", "Delivery / Pick-up Option");}

	public static $plural_name = "Delivery / Pick-up Options";
		function i18n_plural_name() { return _t("PickUpOrDeliveryModifierOptions.DELIVERYOPTION", "Delivery / Pick-up Options");}

	public static $default_sort = "\"IsDefault\" DESC, \"Sort\" ASC, \"Name\" ASC";

	static function default_object() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		if($obj = DataObject::get_one("PickUpOrDeliveryModifierOptions", $filter = "\"IsDefault\" = 1")) {
			return $obj;
		}
		else {
			$obj = new PickUpOrDeliveryModifierOptions();
			$obj->IsDefault = 1;
			$obj->write();
			return $obj;
		}
	}

	static function get_all_as_country_array() {
		$array = array();
		$Options = DataObject::get("PickUpOrDeliveryModifierOptions");
		if($Options) {
			foreach($Options as $option) {
				if($countries = $option->AvailableInCountries()) {
					foreach($countries as $country) {
						$array[$option->Code][] = $country->Code;
					}
				}
			}
		}
		return $array;
	}

	function IsDefaultNice(){
		return $this->IsDefault ? "yes"  : "no";
	}

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$countryField = $this->createManyManyComplexTableField("EcommerceCountry", "AvailableInCountries");
		if($countryField) {
			$fields->replaceField("AvailableInCountries", $countryField);
		}
		$regionField = $this->createManyManyComplexTableField("EcommerceRegion", "AvailableInRegions");
		if($regionField) {
			$fields->replaceField("AvailableInRegions", $regionField);
		}
		if(class_exists("DataObjectSorterController") && $this->hasExtension("DataObjectSorterController")) {
			$fields->addFieldToTab("Root.SortList", new LiteralField("InvitationToSort", $this->dataObjectSorterPopupLink()));
		}
		$fields->replaceField("ExplanationPageID", new OptionalTreeDropdownField($name = "ExplanationPageID", $title = "Link to page explaining postage / delivery (if any)", "SiteTree" ));
		return $fields;
	}

	private function createManyManyComplexTableField($dataObjectName = "EcommerceCountry", $fieldName = "AvailableInCountries") {
		$title = '';
		$field = null;
		$dos = DataObject::get($dataObjectName);
		if($dos) {
			if(class_exists("MultiSelectField")) {
				$array = $dos->toDropdownMap('ID','Title');
				//$name, $title = "", $source = array(), $value = "", $form = null
				$field = new MultiSelectField(
					$fieldName,
					'This option is available in... ',
					$array
				);
			}
			else {
				// $controller,  $name,  $sourceClass, [ $fieldList = null], [ $detailFormFields = null], [ $sourceFilter = ""], [ $sourceSort = ""], [ $sourceJoin = ""]
				$field = new ManyManyComplexTableField(
					$this,
					$fieldName,
					$dataObjectName,
					array('Name' => 'Name'),
					null,
					null,
					"\"Checked\" DESC, \"Name\" ASC"
				);
				$field->setAddTitle("Select locations for which this delivery / pick-up option is available");
				$field->setPermissions(array("export"));
				$field->setPageSize(250);
			}
		}
		if($field) {
			return $field;
		}
		else {
			return new HiddenField($fieldName);
		}
	}

	function onAfterWrite() {
		parent::onAfterWrite();
		// no other record but current one is not default
		if(!$this->IsDefault && !DataObject::get_one("PickUpOrDeliveryModifierOptions", "\"ID\" <> ".intval($this->ID))) {
			DB::query("
				UPDATE \"PickUpOrDeliveryModifierOptions\"
				SET \"IsDefault\" = 1
				WHERE \"ID\" <> ".$this->ID.";");
		}
		//current default -> reset others
		elseif($this->IsDefault) {
			DB::query("
				UPDATE \"PickUpOrDeliveryModifierOptions\"
				SET \"IsDefault\" = 0
				WHERE \"ID\" <> ".intval($this->ID).";");
		}
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$this->Code = eregi_replace("[^[:alnum:]]", " ", $this->Code );
		$this->Code = trim(eregi_replace(" +", "", $this->Code));
		$i = 0;
		if(!$this->Code) {
			$this->Code = self::$defaults["Code"];
		}
		$baseCode = $this->Code;
		while($other = DataObject::get_one("PickUpOrDeliveryModifierOptions", "\"Code\" = '".$this->Code."' AND \"ID\" <> ".$this->ID) && $i < 10){
			$i++;
			$this->Code = $baseCode.'_'.$i;
		}
	}

	/**
	 * returns an array of country IDs that apply to this option (if any)
	 * @return array
	 */
	public function getCountryIDArray(){
		$components = $this->getManyManyComponents('AvailableInCountries');
		if($components && $components->count()) {
			return $components->column("ID");
		}
		else {
			return array();
		}
	}

	/**
	 * returns an array of country Codes that apply to this option (if any)
	 * @return array
	 */
	public function getCountryCodeArray(){
		$components = $this->getManyManyComponents('AvailableInCountries');
		if($components && $components->count()) {
			return $components->column("Code");
		}
		else {
			return array();
		}
	}

	/**
	 * returns an array of country Codes that apply to this option (if any)
	 * @return array
	 */
	public function getRegionIDArray(){
		$components = $this->getManyManyComponents('AvailableInRegions');
		if($components && $components->count()) {
			return $components->column("ID");
		}
		else {
			return array();
		}
	}
}

