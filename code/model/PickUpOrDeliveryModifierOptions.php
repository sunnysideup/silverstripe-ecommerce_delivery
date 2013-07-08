<?php

/**
 * @author nicolaas [at] sunnysideup.co.nz
 * Precondition : There can only be 1 default option
 */
class PickUpOrDeliveryModifierOptions extends DataObject {

	static $db = array(
		"IsDefault" => "Boolean",
		"Code" => "Varchar(25)",
		"Name" => "Varchar(175)",
		"Percentage" => "Double",
		"FixedCost" => "Currency",
		"WeightMultiplier" => "Double",
		"WeightUnit" => "Double",
		"MinimumDeliveryCharge" => "Currency",
		"MaximumDeliveryCharge" => "Currency",
		"MinimumOrderAmountForZeroRate" => "Currency",
		"Sort" => "Int"
	);

	public static $has_one = array(
		"ExplanationPage" => "SiteTree"
	);

	public static $has_many = array(
		"WeightBrackets" => "PickUpOrDeliveryModifierOptions_WeightBracket"
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
		"IsDefaultNice" => "Default option",
		"IsDefault" => "Default delivery option?",
		"Code" => "Code",
		"Name" => "Long Name",
		"Percentage" => "Percentage (number between 0 = 0% and 1 = 100%) of total order cost as charge for this option (e.g. 0.05 would add 5 cents to every dollar ordered).",
		"FixedCost" =>  "Fixed cost (e.g. entering 10 will add a fixed 10 dollars delivery fee).",
		"WeightMultiplier" => "Cost per kilogram. It multiplies the total weight of the total order with this number to work out charge for delivery. NOTE: you can also setup weight brackets (e.g. from 0 - 1.23kg = $123)",
		"WeightUnit" => "Weight unit in kilograms.  If you enter 0.1 here, the price will go up with every 100 grams of total order weight.",
		"MinimumDeliveryCharge" => "Minimum delivery charge.",
		"MaximumDeliveryCharge" => "Maximum delivery charge.",
		"MinimumOrderAmountForZeroRate" => "Minimum for 0 rate (i.e. if this option is selected and the total order is over [enter below] then delivery is free).",
		"Sort" =>  "Sort Index - lower numbers show first."
	);

	public static $defaults = array(
		"Code" => "homedelivery",
		"Name" => "Home Delivery",
		"Percentage" => 0,
		"FixedCost" => 10,
		"WeightMultiplier" => 0,
		"WeightUnit" => 1,
		"MinimumDeliveryCharge" => 10,
		"MaximumDeliveryCharge" => 100,
		"MinimumOrderAmountForZeroRate" => 50,
		"Sort" => 100
	);

	public static $summary_fields = array(
		"IsDefaultNice",
		"Code",
		"Name"
	);

	public static $casting = array(
		"IsDefaultNice" => "Varchar"
	);

	public static $singular_name = "Delivery / Pick-up Option";
		function i18n_singular_name() { return _t("PickUpOrDeliveryModifierOptions.DELIVERYOPTION", "Delivery / Pick-up Option");}

	public static $plural_name = "Delivery / Pick-up Options";
		function i18n_plural_name() { return _t("PickUpOrDeliveryModifierOptions.DELIVERYOPTION", "Delivery / Pick-up Options");}

	public static $default_sort = "\"IsDefault\" DESC, \"Sort\" ASC, \"Name\" ASC";

	/**
	 * returns the default PickUpOrDeliveryModifierOptions object
	 * if none exists, it creates one.
	 * @return PickUpOrDeliveryModifierOptions
	 */
	static function default_object() {
		if($obj = PickUpOrDeliveryModifierOptions::get()->filter(array("IsDefault" => "1"))->First()) {
			//do nothing
		}
		else {
			$obj = new PickUpOrDeliveryModifierOptions();
			$obj->IsDefault = 1;
			$obj->write();
		}
		return $obj;
	}

	/**
	 * returns an array of countries available for all options combined.
	 * like this
	 * array(
	 *	"NZ" => "NZ"
	 * );
	 * @return Array
	 */
	static function get_all_as_country_array() {
		$array = array();
		$options = PickUpOrDeliveryModifierOptions::get();
		if($options->count()) {
			foreach($options as $option) {
				if($countries = $option->AvailableInCountries()) {
					if($countries->count()) {
						foreach($countries as $country) {
							$array[$option->Code][] = $country->Code;
						}
					}
				}
			}
		}
		return $array;
	}

	/**
	 * @return String
	 */
	function IsDefaultNice(){return $this->getIsDefaultNice();}
	function getIsDefaultNice(){
		return $this->IsDefault ? "yes"  : "no";
	}

	/**
	 * standard SS method
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$countryField = $this->createGridField("EcommerceCountry", "AvailableInCountries", "Countries");
		if($countryField) {
			$fields->replaceField("AvailableInCountries", $countryField);
		}
		$regionField = $this->createGridField("EcommerceRegion", "AvailableInRegions", "Regions");
		if($regionField) {
			$fields->replaceField("AvailableInRegions", $regionField);
		}
		if(class_exists("DataObjectSorterController") && $this->hasExtension("DataObjectSorterController")) {
			$fields->addFieldToTab("Root.SortList", new LiteralField("InvitationToSort", $this->dataObjectSorterPopupLink()));
		}
		$fields->replaceField("ExplanationPageID", new OptionalTreeDropdownField($name = "ExplanationPageID", $title = "Link to page explaining postage / delivery (if any)", "SiteTree" ));

		//add headings
		$fields->addFieldToTab("Root.Main", new HeaderField("Charges", "Other Charges (enter zero (0) to ignore)"), "Percentage");
		$fields->addFieldToTab("Root.Main", new HeaderField("MinimumAndMaximum", "Minimum and Maximum (enter zero (0) to ignore)"), "MinimumDeliveryCharge");
		if(EcommerceDBConfig::current_ecommerce_db_config()->ProductsHaveWeight) {
			$weightBrackets = $this->WeightBrackets();
			if($weightBrackets && $weightBrackets->count()) {
				$fields->removeByName("WeightMultiplier");
				$fields->removeByName("WeightUnit");
			}
			else {
				$fields->addFieldToTab("Root.Main", new HeaderField("WeightOptions", "Weight Options (also see Weight Brackets tab)"), "WeightMultiplier");
			}
		}
		else {
			$fields->removeByName("WeightBrackets");
			$fields->removeByName("WeightMultiplier");
			$fields->removeByName("WeightUnit");
		}
		$fields->addFieldToTab("Root.Main", new HeaderField("MoreInformation", "Other Settings"), "Sort");
		return $fields;
	}

	private function createGridField($dataObjectName = "EcommerceCountry", $fieldName = "AvailableInCountries", $title) {
		$title = '';
		$field = null;
		$dos = $dataObjectName::get();
		if($dos->count()) {
			if(class_exists("MultiSelectField")) {
				$array = $dos->map('ID','Title')->toArray();
				//$name, $title = "", $source = array(), $value = "", $form = null
				$field = new MultiSelectField(
					$fieldName,
					'This option is available in... ',
					$array
				);
			}
			else {
				// $controller,  $name,  $sourceClass, [ $fieldList = null], [ $detailFormFields = null], [ $sourceFilter = ""], [ $sourceSort = ""], [ $sourceJoin = ""]

				$gridFieldConfig = GridFieldConfig::create();
				$gridFieldConfig->addComponent(new GridFieldButtonRow('before'));
				$gridFieldConfig->addComponent(new GridFieldAddExistingAutocompleter('buttons-before-left'));
				$gridFieldConfig->addComponent(new GridFieldToolbarHeader());
				$gridFieldConfig->addComponent($sort = new GridFieldSortableHeader());
				$gridFieldConfig->addComponent($filter = new GridFieldFilterHeader());
				$gridFieldConfig->addComponent(new GridFieldDataColumns());
				$gridFieldConfig->addComponent(new GridFieldEditButton());
				$gridFieldConfig->addComponent(new GridFieldDeleteAction(true));
				$gridFieldConfig->addComponent(new GridFieldPageCount('toolbar-header-right'));
				$gridFieldConfig->addComponent($pagination = new GridFieldPaginator());
				$gridFieldConfig->addComponent(new GridFieldDetailForm());

				$source = $this->$fieldName();
				return new GridField($fieldName, _t("PickUpOrDeliverModifierOptions.AVAILABLEINCOUNTRIES", "Available in ".$title), $source , $gridFieldConfig);
			}
		}
		if($field) {
			return $field;
		}
		else {
			return new HiddenField($fieldName);
		}
	}

	/**
	 * make sure there is only exactly one default
	 */
	function onAfterWrite() {
		parent::onAfterWrite();
		// no other record but current one is not default
		if((!$this->IsDefault) && (PickUpOrDeliveryModifierOptions::get()->Exclude(array("ID" => intval($this->ID)))->count() == 0)) {
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

	/**
	 * make sure all are unique codes
	 */
	function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->Code = trim(preg_replace("/[^a-zA-Z0-9]+/", "", $this->Code));
		$i = 0;
		if(!$this->Code) {
			$this->Code = self::$defaults["Code"];
		}
		$baseCode = $this->Code;
		while(PickUpOrDeliveryModifierOptions::get()->Filter(array("Code" => $this->Code))->Exclude(array("ID" => $this->ID))->count() && $i < 10){
			$i++;
			$this->Code = $baseCode.'_'.$i;
		}
		if($this->MinimumDeliveryCharge && $this->MaximumDeliveryCharge) {
			if($this->MinimumDeliveryCharge > $this->MaximumDeliveryCharge) {
				$this->MinimumDeliveryCharge = $this->MaximumDeliveryCharge;
			}
		}
	}
}


/**
 * below we record options for weight brackets with fixed cost
 * e.g. if Order.Weight > 10 and Order.Weight < 20 => Charge is $111.
 *
 *
 *
 */
class PickUpOrDeliveryModifierOptions_WeightBracket extends DataObject {

	static $db = array(
		"Name" => "Varchar",
		"MinimumWeight" => "Int",
		"MaximumWeight" => "Int",
		"FixedCost" => "Currency"
	);

	static $has_one = array(
		"Option" => "PickUpOrDeliveryModifierOptions"
	);

	public static $indexes = array(
		"MinimumWeight" => true,
		"MaximumWeight" => true
	);

	public static $searchable_fields = array(
		"Name" => "PartialMatchFilter"
	);

	public static $field_labels = array(
		"Name" => "Description (e.g. small parcel)",
		"MinimumWeight" => "The minimum weight in grams",
		"MaximumWeight" => "The maximum weight in grams",
		"FixedCost" => "Total price (fixed cost)"
	);

	public static $summary_fields = array(
		"Name",
		"MinimumWeight",
		"MaximumWeight",
		"FixedCost"
	);

	public static $singular_name = "Weight Bracket";
		function i18n_singular_name() { return _t("PickUpOrDeliveryModifierOptions.WEIGHTBRACKET", "Weight Bracket");}

	public static $plural_name = "Weight Brackets";
		function i18n_plural_name() { return _t("PickUpOrDeliveryModifierOptions.WEIGHTBRACKETS", "Weight Brackets");}

	public static $default_sort = "MinimumWeight ASC, MaximumWeight ASC";

}
