<?php

/**
 * @author nicolaas [at] sunnysideup.co.nz
 * Precondition : There can only be 1 default option
 */
class PickUpOrDeliveryModifierOptions extends DataObject
{

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD: private static $db (case sensitive)
  * NEW: 
    private static $table_name = '[SEARCH_REPLACE_CLASS_NAME_GOES_HERE]';

    private static $db (COMPLEX)
  * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    
    private static $table_name = 'PickUpOrDeliveryModifierOptions';

    private static $db = array(
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
        "FreeShippingUpToThisOrderAmount" => "Currency",
        "Sort" => "Int"
    );

    private static $has_one = array(
        "ExplanationPage" => "SiteTree"
    );

    private static $many_many = array(
        "AvailableInCountries" => "EcommerceCountry",
        "AvailableInRegions" => "EcommerceRegion",
        "WeightBrackets" => "PickUpOrDeliveryModifierOptions_WeightBracket",
        "SubtotalBrackets" => "PickUpOrDeliveryModifierOptions_SubTotalBracket",
        "ExcludedProducts" => 'Product'
    );

    private static $belongs_many_many = array(
        "ExcludeFromCountries" => "EcommerceCountry",
    );

    private static $indexes = array(
        "IsDefault" => true,
        "Code" => true
    );

    private static $searchable_fields = array(
        "Code",
        "Name" => "PartialMatchFilter"
    );

    private static $field_labels = array(
        "IsDefaultNice" => "Default option",
        "IsDefault" => "Default delivery option?",
        "Code" => "Code",
        "Name" => "Long Name",
        "Percentage" => "Percentage",
        "FixedCost" =>  "Fixed cost",
        "WeightMultiplier" => "Cost per kilogram",
        "WeightUnit" => "Weight unit in kilograms",
        "MinimumDeliveryCharge" => "Minimum delivery charge",
        "MaximumDeliveryCharge" => "Maximum delivery charge",
        "MinimumOrderAmountForZeroRate" => "Minimum for 0 rate",
        "FreeShippingUpToThisOrderAmount" => "Free shipping up to",
        "Sort" =>  "Sort Index",
        "ListOfCountries" =>  "Applicable Countries"
    );

    private static $field_labels_right = array(
        "Percentage" => "number between 0 = 0% and 1 = 100% (e.g. 0.05 would add 5 cents to every dollar ordered).",
        "FixedCost" =>  "e.g. entering 10 will add a fixed 10 dollars (or whatever currency is being used) delivery fee.",
        "WeightMultiplier" => "it multiplies the total weight of the total order with this number to work out charge for delivery. NOTE: you can also setup weight brackets (e.g. from 0 - 1kg = $123, from 1kg - 2kg = $456).",
        "WeightUnit" => "if you enter 0.1 here, the price will go up with every 100 grams of total order weight.",
        "MinimumDeliveryCharge" => "minimum delivery charge.",
        "MaximumDeliveryCharge" => "maximum delivery charge.",
        "MinimumOrderAmountForZeroRate" => "if this option is selected and the total order is over the amounted entered above then delivery is free.",
        "FreeShippingUpToThisOrderAmount" => "if this option is selected and the total order is less than the amount entered above then delivery is free. This is for situations where a small order would have a large delivery cost.",
        "Sort" =>  "lower numbers show first."
    );

    private static $defaults = array(
        "Code" => "homedelivery",
        "Name" => "Home Delivery",
        "Percentage" => 0,
        "FixedCost" => 0,
        "WeightMultiplier" => 0,
        "WeightUnit" => 1,
        "MinimumDeliveryCharge" => 0,
        "MaximumDeliveryCharge" => 0,
        "MinimumOrderAmountForZeroRate" => 0,
        "Sort" => 0
    );

    private static $summary_fields = array(
        "IsDefaultNice",
        "Code",
        "Name",
        'ListOfCountries'
    );

    private static $casting = array(
        "IsDefaultNice" => "Varchar",
        "ListOfCountries" => "Varchar"
    );

    private static $singular_name = "Delivery / Pick-up Option";
    public function i18n_singular_name()
    {
        return _t("PickUpOrDeliveryModifierOptions.DELIVERYOPTION", "Delivery / Pick-up Option");
    }

    private static $plural_name = "Delivery / Pick-up Options";
    public function i18n_plural_name()
    {
        return _t("PickUpOrDeliveryModifierOptions.DELIVERYOPTION", "Delivery / Pick-up Options");
    }

    private static $default_sort = "\"IsDefault\" DESC, \"Sort\" ASC, \"Name\" ASC";

    /**
     * returns the default PickUpOrDeliveryModifierOptions object
     * if none exists, it creates one.
     * @return PickUpOrDeliveryModifierOptions
     */
    public static function default_object()
    {
        if ($obj = PickUpOrDeliveryModifierOptions::get()->filter(array("IsDefault" => "1"))->First()) {
            //do nothing
        } else {
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
    public static function get_all_as_country_array()
    {
        $array = [];
        $options = PickUpOrDeliveryModifierOptions::get();
        if ($options->count()) {
            foreach ($options as $option) {
                if ($countries = $option->AvailableInCountries()) {
                    if ($countries->count()) {
                        foreach ($countries as $country) {
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
    public function IsDefaultNice()
    {
        return $this->getIsDefaultNice();
    }
    public function getIsDefaultNice()
    {
        return $this->IsDefault ? "yes"  : "no";
    }


    /**
     * standard SS method
     * @param Member | NULL
     * @return Boolean
     */
    public function canCreate($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
            return true;
        }
        return parent::canCreate($member);
    }

    /**
     * standard SS method
     * @param Member | NULL
     * @return Boolean
     */
    public function canView($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
            return true;
        }
        return parent::canCreate($member);
    }

    /**
     * standard SS method
     * @param Member | NULL
     * @return Boolean
     */
    public function canEdit($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
            return true;
        }
        return parent::canEdit($member);
    }

    /**
     * standard SS method
     * @param Member | NULL
     * @return Boolean
     */
    public function canDelete($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
            return true;
        }
        return parent::canDelete($member);
    }

    /**
     * standard SS method
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $availableInCountriesField = $this->createGridField("EcommerceCountry", "AvailableInCountries", "Available in");
        if ($availableInCountriesField) {
            $fields->replaceField("AvailableInCountries", $availableInCountriesField);
        }
        $excludeFromCountriesField = $this->createGridField("EcommerceCountry", "ExcludeFromCountries", "Excluded from");
        if ($excludeFromCountriesField) {
            $fields->replaceField("ExcludeFromCountries", $excludeFromCountriesField);
        }
        $regionField = $this->createGridField("EcommerceRegion", "AvailableInRegions", "Regions");
        if ($regionField) {
            $fields->replaceField("AvailableInRegions", $regionField);
        }
        if (class_exists("DataObjectSorterController") && $this->hasExtension("DataObjectSorterController")) {
            $fields->addFieldToTab("Root.Sort", new LiteralField("InvitationToSort", $this->dataObjectSorterPopupLink()));
        }
        $fields->replaceField("ExplanationPageID", new OptionalTreeDropdownField($name = "ExplanationPageID", $title = "Page", "SiteTree"));

        //add headings
        $fields->addFieldToTab(
            "Root.Main",
            new HeaderField(
                "Charges",
                _t("PickUpOrDeliveryModifierOptions.CHARGES", "Charges (enter zero (0) to ignore)")
            ),
            "Percentage"
        );
        $fields->addFieldToTab(
            "Root.Main",
            new HeaderField(
                "MinimumAndMaximum",
                _t("PickUpOrDeliveryModifierOptions.MIN_AND_MAX", "Minimum and Maximum (enter zero (0) to ignore)")
            ),
            "MinimumDeliveryCharge"
        );
        $fields->addFieldToTab(
            "Root.Main",
            new HeaderField(
                "ExplanationHeader",
                _t("PickUpOrDeliveryModifierOptions.EXPLANATION_HEADER", "More information about delivery option")
            ),
            "ExplanationPageID"
        );
        $fields->replaceField(
            'ExcludedProducts',
            $excludedProdsField = GridField::create(
                'ExcludedProducts',
                'Excluded Products',
                $this->ExcludedProducts(),
                $config = GridFieldBasicPageRelationConfig::create()
            )
        );
        $excludedProdsField->setDescription("Products added here will not be charged delivery costs. If a customer's order contains more than one item (and not all items are listed here), then delivery costs will still be calculated.");
        if (EcommerceDBConfig::current_ecommerce_db_config()->ProductsHaveWeight) {
            $weightBrackets = $this->WeightBrackets();
            if ($weightBrackets && $weightBrackets->count()) {
                $fields->removeByName("WeightMultiplier");
                $fields->removeByName("WeightUnit");
            } else {
                $fields->addFieldToTab("Root.Main", new HeaderField("WeightOptions", "Weight Options (also see Weight Brackets tab)"), "WeightMultiplier");
            }
        } else {
            $fields->removeByName("WeightBrackets");
            $fields->removeByName("WeightMultiplier");
            $fields->removeByName("WeightUnit");
        }
        $fields->addFieldToTab("Root.Main", new HeaderField("MoreInformation", "Other Settings"), "Sort");
        foreach ($this->Config()->get("field_labels_right") as $fieldName => $fieldDescription) {
            $field = $fields->dataFieldByName($fieldName);
            if ($field) {
                $field->setRightTitle($fieldDescription);
            }
        }
        return $fields;
    }

    private function createGridField($dataObjectName = "EcommerceCountry", $fieldName = "AvailableInCountries", $title)
    {
        $field = null;
        $dos = $dataObjectName::get();
        if ($dos->count()) {
            if (class_exists("MultiSelectField")) {
                $array = $dos->map('ID', 'Title')->toArray();
                //$name, $title = "", $source = array(), $value = "", $form = null
                $field = new MultiSelectField(
                    $fieldName,
                    'This option is available in... ',
                    $array
                );
            } else {
                // $controller,  $name,  $sourceClass, [ $fieldList = null], [ $detailFormFields = null], [ $sourceFilter = ""], [ $sourceSort = ""], [ $sourceJoin = ""]
                /**
                * @todo: Auto completer may not be functioning correctly: ExactMatchFilter does not accept EcommerceCountryFilters_AllowSales as modifiers
                *
                */

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
                return new GridField($fieldName, _t("PickUpOrDeliverModifierOptions.AVAILABLEINCOUNTRIES", "".$title), $source, $gridFieldConfig);
            }
        }
        if ($field) {
            return $field;
        } else {
            return new HiddenField($fieldName);
        }
    }

    /**
     * make sure there is only exactly one default
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        // no other record but current one is not default
        if ((!$this->IsDefault) && (PickUpOrDeliveryModifierOptions::get()->exclude(array("ID" => intval($this->ID)))->count() == 0)) {
            DB::query("
                UPDATE \"PickUpOrDeliveryModifierOptions\"
                SET \"IsDefault\" = 1
                WHERE \"ID\" <> ".$this->ID.";");
        }
        //current default -> reset others
        elseif ($this->IsDefault) {
            DB::query("
                UPDATE \"PickUpOrDeliveryModifierOptions\"
                SET \"IsDefault\" = 0
                WHERE \"ID\" <> ".intval($this->ID).";");
        }
    }

    /**
     * make sure all are unique codes
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Code = trim(preg_replace("/[^a-zA-Z0-9]+/", "", $this->Code));
        $i = 0;
        if (!$this->Code) {
            $defaults = $this->Config()->get("Code");
            $this->Code = empty($defaults["Code"]) ? "CODE" : $defaults["Code"];
        }
        $baseCode = $this->Code;
        while (PickUpOrDeliveryModifierOptions::get()->filter(array("Code" => $this->Code))->exclude(array("ID" => $this->ID))->count() && $i < 100) {
            $i++;
            $this->Code = $baseCode.'_'.$i;
        }
        if ($this->MinimumDeliveryCharge && $this->MaximumDeliveryCharge) {
            if ($this->MinimumDeliveryCharge > $this->MaximumDeliveryCharge) {
                $this->MinimumDeliveryCharge = $this->MaximumDeliveryCharge;
            }
        }
    }

    public function getListOfCountries()
    {
        $in = '';
        $out = '';
        if ($this->AvailableInCountries()->count()) {
            $in = "".implode(', ', $this->AvailableInCountries()->column("Code"));
        }
        if ($this->ExcludeFromCountries()->count()) {
            $out = " // OUT: ".implode(', ', $this->ExcludeFromCountries()->column("Code"));
        }
        return $in.$out;
    }
}
