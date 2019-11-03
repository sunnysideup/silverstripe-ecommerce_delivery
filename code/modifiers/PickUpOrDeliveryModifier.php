<?php

/**
 * @author Nicolaas [at] sunnysideup.co.nz
 * @package: ecommerce
 * @sub-package: ecommerce_delivery
 * @description: Shipping calculation scheme based on SimpleShippingModifier.
 * It lets you set fixed shipping costs, or a fixed
 * cost for each region you're delivering to.
 */
class PickUpOrDeliveryModifier extends OrderModifier
{

// ######################################## *** model defining static variables (e.g. $db, $has_one)

    private static $db = array(
        "TotalWeight" => "Double",
        "RegionAndCountry" => "Varchar",
        "SerializedCalculationObject" => "Text",
        "DebugString" => "HTMLText",
        "SubTotalAmount" => "Currency"
    );

    private static $defaults = [
        'Type' => 'Delivery'
    ];

    private static $has_one = array(
        "Option" => "PickUpOrDeliveryModifierOptions"
    );

    private static $singular_name = "Pickup / Delivery Charge";
    public function i18n_singular_name()
    {
        return _t("PickUpOrDeliveryModifier.DELIVERYCHARGE", "Delivery / Pick-up");
    }

    private static $plural_name = "Pickup / Delivery Charges";
    public function i18n_plural_name()
    {
        return _t("PickUpOrDeliveryModifier.DELIVERYCHARGES", "Delivery / Pick-up");
    }

    private static $include_form_in_order_table = true;


    // ######################################## *** cms variables + functions (e.g. getCMSFields, $searchableFields)

    public function getCMSFields()
    {
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


    // ######################################## *** other (non) static variables (e.g. private static $special_name_for_something, protected $order)

    /**
     *@var String $weight_field - the field used in the Buyable to work out the weight.
     *
     */
    private static $weight_field = 'Weight';

    /**
     * @var Float $total_weight
     * the total amount of weight for the order
     * saved here for speed's sake
     */
    private static $_total_weight = null;

    /**
     * @var DataList
     */
    private static $available_options = null;

    /**
     * @var PickUpOrDeliveryModifierOptions
     * The most applicable option
     */
    private static $selected_option = null;

    /**
     * @var Double
     * the total amount charged in the end.
     * saved here for speed's sake
     */
    private static $_actual_charges = 0;

    /**
     * @var Boolean
     * the total amount charged in the end
     * saved here for speed's sake
     */
    private static $calculations_done = false;

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
    public function setOption($optionID)
    {
        $optionID = intval($optionID);
        $this->OptionID = $optionID;
        $this->write();
    }

    /**
     * updates database fields
     * @param Bool $force - run it, even if it has run already
     * @return void
     */
    public function runUpdate($force = true)
    {
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
        parent::runUpdate($force);
    }

    // ######################################## *** form functions (e. g. Showform and getform)



    /**
     * standard Modifier Method
     * @return Boolean
     */
    public function ShowForm()
    {
        if ($this->ShowInTable()) {
            if ($this->Order()->Items()) {
                if ($options = $this->liveOptions()) {
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
    public function ShowFormInEditableOrderTable()
    {
        return ($this->ShowForm() && $this->Config()->get("include_form_in_order_table")) ? true : false;
    }

    /**
     *
     * @return Form
     */
    public function getModifierForm(Controller $optionalController = null, Validator $optionalValidator = null)
    {
        Requirements::themedCSS("PickUpOrDeliveryModifier", "ecommerce_delivery");
        Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
        //Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
        //Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
        Requirements::javascript(THIRDPARTY_DIR."/jquery-form/jquery.form.js");
        Requirements::javascript("ecommerce_delivery/javascript/PickUpOrDeliveryModifier.js");
        $array = PickUpOrDeliveryModifierOptions::get_all_as_country_array();
        if ($array && is_array($array) && count($array)) {
            $js = "\n".'var PickUpOrDeliveryModifierOptions = []';
            $count = 0;
            foreach ($array as $key => $option) {
                if ($option && is_array($option) && count($option)) {
                    if ($count == 0) {
                        $js .= "\n".'    PickUpOrDeliveryModifierOptions["'.$key.'"] = new Array("'.implode('","', $option).'")';
                    } else {
                        $js .= "\n".'    PickUpOrDeliveryModifierOptions["'.$key.'"] = new Array("'.implode('","', $option).'")';
                    }
                    $count++;
                }
            }
            if ($js) {
                //add final semi-comma
                $js .= "";
                Requirements::customScript($js, "PickupOrDeliveryModifier");
            }
        }
        $fields = new FieldList();
        $fields->push($this->headingField());
        $fields->push($this->descriptionField());
        $options = $this->liveOptions()->map('ID', 'Name');//$this->getOptionListForDropDown();
        $optionID = $this->LiveOptionID();
        $fields->push(OptionSetField::create('PickupOrDeliveryType', 'Preference', $options, $optionID));
        $actions = new FieldList(
            new FormAction('processOrderModifier', 'Update Pickup / Delivery Option')
        );
        return new PickUpOrDeliveryModifier_Form($optionalController, 'PickUpOrDeliveryModifier', $fields, $actions, $optionalValidator);
    }

    // ######################################## *** template functions (e.g. ShowInTable, TableTitle, etc...) ... USES DB VALUES

    /**
     * @return Boolean
     */
    public function ShowInTable()
    {
        return true;
    }

    /**
     * @return Boolean
     */
    public function CanBeRemoved()
    {
        return false;
    }

    /**
     * NOTE: the function below is  HACK and needs fixing proper.
     *
     */
    public function CartValue()
    {
        return $this->getCartValue();
    }
    public function getCartValue()
    {
        return $this->LiveCalculatedTotal();
    }

    // ######################################## ***  inner calculations.... USES CALCULATED VALUES

    /**
     * returns the current selected option as object
     * @return PickUpOrDeliveryModifierOptions;
     */
    protected function LiveOptionObject()
    {
        return PickUpOrDeliveryModifierOptions::get()->byID($this->LiveOptionID());
    }

    /**
     * works out if Weight is applicable at all
     * @return Boolean
     */
    protected function useWeight()
    {
        return EcommerceDBConfig::current_ecommerce_db_config()->ProductsHaveWeight;
    }

    /**
     * Returns the available delivery options based on the current country and region
     * for the order.
     * Must always return something!
     * @return DataList
     */
    protected function LiveOptions()
    {
        if (!self::$available_options) {
            $countryID = EcommerceCountry::get_country_id();
            $regionID = EcommerceRegion::get_region_id();
            $weight = $this->LiveTotalWeight();
            $options = PickUpOrDeliveryModifierOptions::get();
            if ($options->count()) {
                foreach ($options as $option) {
                    //check countries
                    if ($countryID) {
                        $availableInCountriesList = $option->AvailableInCountries();
                        //exclude if not found in country list
                        if ($availableInCountriesList->Count() > 0 && ! $availableInCountriesList->find('ID', $countryID)) {
                            continue;
                        }
                        //exclude if in exclusion list
                        $excludedFromCountryList = $option->ExcludeFromCountries();
                        if ($excludedFromCountryList->Count() > 0 && $excludedFromCountryList->find('ID', $countryID)) {
                            continue;
                        }
                    }
                    //check regions
                    if ($regionID) {
                        $optionRegions = $option->AvailableInRegions();
                        //exclude if not found in region list
                        if ($optionRegions->Count() > 0 && ! $optionRegions->find('ID', $regionID)) {
                            continue;
                        }
                    }
                    $result[] = $option;
                }
            }
            if (! isset($result)) {
                $result[] = PickUpOrDeliveryModifierOptions::default_object();
            }
            self::$available_options = new ArrayList($result);
        }
        return self::$available_options;
    }


    protected function LiveType()
    {
        return 'Delivery';
    }
    // ######################################## *** calculate database fields: protected function Live[field name]  ... USES CALCULATED VALUES

    /**
     * Precondition : There are always options available.
     * @return Int
     */
    protected function LiveOptionID()
    {
        if (!self::$selected_option) {
            $options = $this->liveOptions();
            if (self::$selected_option = $options->find('ID', $this->OptionID)) {
                //do nothing;
            } else {
                self::$selected_option = $options->find('IsDefault', 1);
                if (! self::$selected_option) {
                    self::$selected_option = $options->First();
                }
            }
        }
        return self::$selected_option->ID;
    }

    /**
     * @return String
     */
    protected function LiveName()
    {
        $obj = $this->liveOptionObject();
        if (is_object($obj)) {
            $v = $obj->Name;
            if ($obj->ExplanationPageID) {
                $page = $obj->ExplanationPage();
                if ($page) {
                    $v .= '<div id="PickUpOrDeliveryModifierExplanationLink"><a href="'.$page->Link().'" class="externalLink">'.convert::raw2sql($page->Title).'</a></div>';
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
    protected function LiveSubTotalAmount()
    {
        $order = $this->Order();
        return $order->SubTotal();
    }

    /**
     * description of region and country being shipped to.
     * @return PickUpOrDeliveryModifierOptions | NULL
     */
    protected function LiveSerializedCalculationObject()
    {
        $obj = $this->liveOptionObject();
        if ($obj) {
            return serialize($obj);
        }
    }

    /**
     * description of region and country being shipped to.
     * @return String
     */
    protected function LiveRegionAndCountry()
    {
        $details = array();
        $option = $this->Option();
        if ($option) {
            $regionID = EcommerceRegion::get_region_id();
            if ($regionID) {
                $region = EcommerceRegion::get()->byID($regionID);
                if ($region) {
                    $details[] = $region->Name;
                }
            }
            $countryID = EcommerceCountry::get_country_id();
            if ($countryID) {
                $country = EcommerceCountry::get()->byID($countryID);
                if ($country) {
                    $details[] = $country->Name;
                }
            }
        } else {
            return _t("PickUpOrDeliveryModifier.NOTSELECTED", "No delivery option has been selected");
        }
        if (count($details)) {
            return implode(", ", $details);
        }
    }

    /**
    * @return Double
    **/
    protected function LiveCalculatedTotal()
    {
        //________________ start caching mechanism
        if (self::$calculations_done) {
            return self::$_actual_charges;
        }
        self::$calculations_done = true;
        //________________ end caching mechanism

        self::$_actual_charges = 0;
        //do we have enough information
        $obj = $this->liveOptionObject();
        $items = $this->Order()->Items();
        if (is_object($obj) && $obj->exists() && $items && $items->count()) {


            //are ALL products excluded?
            if ($obj->ExcludedProducts() && $obj->ExcludedProducts()->count()) {
                $hasIncludedProduct = false;
                $excludedProductIDArray = $obj->ExcludedProducts()->column('ID');
                //are all the products excluded?
                foreach ($items as $orderItem) {
                    $product = $orderItem->Product();
                    if ($product) {
                        if (in_array($product->ID, $excludedProductIDArray)) {
                            //do nothing
                        } else {
                            $hasIncludedProduct = true;
                            break;
                        }
                    }
                }
                if ($hasIncludedProduct === false) {
                    $this->debugMessage .= "<hr />all products are excluded from delivery charges";
                    return self::$_actual_charges;
                }
            }

            $this->debugMessage .= "<hr />option selected: ".$obj->Title.", and items present";
            //lets check sub-total
            $subTotalAmount = $this->LiveSubTotalAmount();
            $this->debugMessage .= "<hr />sub total amount is: \$". $subTotalAmount;
            // no need to charge, order is big enough
            $minForZeroRate = floatval($obj->MinimumOrderAmountForZeroRate);
            $maxForZeroRate = floatval($obj->FreeShippingUpToThisOrderAmount);

            $weigth = $weight = $this->LiveTotalWeight();
            $weightBrackets = $obj->WeightBrackets();
            $subTotalBrackets = $obj->SubTotalBrackets();

            // zero becauase over minForZeroRate
            if ($minForZeroRate > 0 && $minForZeroRate < $subTotalAmount) {
                self::$_actual_charges =  0;
                $this->debugMessage .= "<hr />Minimum Order Amount For Zero Rate: ".$obj->MinimumOrderAmountForZeroRate." is lower than amount  ordered: ".self::$_actual_charges;
            }

            //zero because below maxForZeroRate
            elseif ($maxForZeroRate > 0 && $maxForZeroRate > $subTotalAmount) {
                self::$_actual_charges =  0;
                $this->debugMessage .= "<hr />Maximum Order Amount For Zero Rate: ".$obj->FreeShippingUpToThisOrderAmount." is higher than amount ordered: ".self::$_actual_charges;
            } else {
                //examine weight brackets
                if ($weight && $weightBrackets->count()) {
                    $this->debugMessage .= "<hr />there is weight: {$weight}gr.";
                    //weight brackets
                    $foundWeightBracket = null;
                    $weightBracketQuantity = 1;
                    $additionalWeightBracket = null;
                    $minimumMinimum = null;
                    $maximumMaximum = null;
                    foreach ($weightBrackets as $weightBracket) {
                        if ((! $foundWeightBracket) && ($weightBracket->MinimumWeight <= $weight) && ($weight <= $weightBracket->MaximumWeight)) {
                            $foundWeightBracket = $weightBracket;
                        }
                        //look for absolute min and max
                        if ($minimumMinimum === null || ($weightBracket->MinimumWeight > $minimumMinimum->MinimumWeight)) {
                            $minimumMinimum = $weightBracket;
                        }
                        if ($maximumMaximum === null || ($weightBracket->MaximumWeight > $maximumMaximum->MaximumWeight)) {
                            $maximumMaximum = $weightBracket;
                        }
                    }
                    if (! $foundWeightBracket) {
                        if ($weight < $minimumMinimum->MinimumWeight) {
                            $foundWeightBracket = $minimumMinimum;
                        } elseif ($weight > $maximumMaximum->MaximumWeight) {
                            $foundWeightBracket = $maximumMaximum;
                            $weightBracketQuantity = floor($weight / $maximumMaximum->MaximumWeight);
                            $restWeight = $weight - ($maximumMaximum->MaximumWeight * $weightBracketQuantity);
                            $additionalWeightBracket = null;
                            foreach ($weightBrackets as $weightBracket) {
                                if (($weightBracket->MinimumWeight <= $restWeight) && ($restWeight <= $weightBracket->MaximumWeight)) {
                                    $additionalWeightBracket = $weightBracket;
                                    break;
                                }
                            }
                        }
                    }
                    //we found some applicable weight brackets
                    if ($foundWeightBracket) {
                        self::$_actual_charges += $foundWeightBracket->FixedCost * $weightBracketQuantity;
                        $this->debugMessage .= "<hr />found Weight Bracket (from {$foundWeightBracket->MinimumWeight}gr. to {$foundWeightBracket->MaximumWeight}gr.): \${$foundWeightBracket->FixedCost} ({$foundWeightBracket->Name}) from  times $weightBracketQuantity";
                        if ($additionalWeightBracket) {
                            self::$_actual_charges += $additionalWeightBracket->FixedCost;
                            $this->debugMessage .= "<hr />+ additional Weight Bracket (from {$additionalWeightBracket->MinimumWeight}gr. to {$additionalWeightBracket->MaximumWeight}gr.): \${$additionalWeightBracket->FixedCost} ({$foundWeightBracket->Name})";
                        }
                    }
                }



                // weight based on multiplier ...
                elseif ($weight && $obj->WeightMultiplier) {
                    // add weight based shipping
                    if (!$obj->WeightUnit) {
                        $obj->WeightUnit = 1;
                    }
                    $this->debugMessage .= "<hr />actual weight:".$weight." multiplier = ".$obj->WeightMultiplier." weight unit = ".$obj->WeightUnit." ";
                    //legacy fix
                    $units = ceil($weight / $obj->WeightUnit);
                    $weightCharge =  $units * $obj->WeightMultiplier;
                    self::$_actual_charges += $weightCharge;
                    $this->debugMessage .= "<hr />weight charge: ".$weightCharge;
                }


                //examine price brackets
                elseif ($subTotalAmount && $subTotalBrackets->count()) {
                    $this->debugMessage .= "<hr />there is subTotal: {$subTotalAmount} and subtotal brackets.";
                    //subTotal brackets
                    $foundSubTotalBracket = null;
                    foreach ($subTotalBrackets as $subTotalBracket) {
                        if ((! $foundSubTotalBracket) && ($subTotalBracket->MinimumSubTotal <= $subTotalAmount) && ($subTotalAmount <= $subTotalBracket->MaximumSubTotal)) {
                            $foundSubTotalBracket = $subTotalBracket;
                            break;
                        }
                    }
                    //we found some applicable subTotal brackets
                    if ($foundSubTotalBracket) {
                        self::$_actual_charges += $foundSubTotalBracket->FixedCost;
                        $this->debugMessage .= "<hr />found SubTotal Bracket (between {$foundSubTotalBracket->MinimumSubTotal} and {$foundSubTotalBracket->MaximumSubTotal}): \${$foundSubTotalBracket->FixedCost} ({$foundSubTotalBracket->Name}) ";
                    }
                }

                // add percentage
                if ($obj->Percentage) {
                    $percentageCharge = $subTotalAmount * $obj->Percentage;
                    self::$_actual_charges += $percentageCharge;
                    $this->debugMessage .= "<hr />percentage charge: \$".$percentageCharge;
                }

                // add fixed price
                if ($obj->FixedCost <> 0) {
                    self::$_actual_charges += $obj->FixedCost;
                    $this->debugMessage .= "<hr />fixed charge: \$". $obj->FixedCost;
                }
            }
            //is it enough?
            if (self::$_actual_charges < $obj->MinimumDeliveryCharge && $obj->MinimumDeliveryCharge > 0) {
                $oldActualCharge = self::$_actual_charges;
                self::$_actual_charges = $obj->MinimumDeliveryCharge;
                $this->debugMessage .= "<hr />too little: actual charge: ".$oldActualCharge.", minimum delivery charge: ".$obj->MinimumDeliveryCharge;
            }
            // is it too much
            if (self::$_actual_charges > $obj->MaximumDeliveryCharge  && $obj->MaximumDeliveryCharge > 0) {
                self::$_actual_charges = $obj->MaximumDeliveryCharge;
                $this->debugMessage .= "<hr />too much: ".self::$_actual_charges.", maximum delivery charge is ".$obj->MaximumDeliveryCharge;
            }
        } else {
            if (!$items) {
                $this->debugMessage .= "<hr />no items present";
            } else {
                $this->debugMessage .= "<hr />no delivery option available";
            }
        }
        $this->debugMessage .= "<hr />final score: \$".self::$_actual_charges;
        //special case, we are using weight and there is no weight!
        return self::$_actual_charges;
    }

    /**
     *
     *
     * @return Double
     */
    protected function LiveTotalWeight()
    {
        if (self::$_total_weight === null) {
            self::$_total_weight = 0;
            if ($this->useWeight()) {
                if ($fieldName = Config::inst()->get('PickUpOrDeliveryModifier', 'weight_field')) {
                    $items = $this->Order()->Items();
                    //get index numbers for bonus products - this can only be done now once they have actually been added
                    if ($items && $items->count()) {
                        foreach ($items as $itemIndex => $item) {
                            $buyable = $item->Buyable();
                            if ($buyable) {
                                // Calculate the total weight of the order
                                if (! empty($buyable->$fieldName) && $item->Quantity) {
                                    self::$_total_weight += $buyable->$fieldName * $item->Quantity;
                                }
                            }
                        }
                    }
                }
            }
        }
        return self::$_total_weight;
    }

    /**
     * returns an explanation of cost.
     * @return String
     */
    protected function LiveDebugString()
    {
        return $this->debugMessage;
    }


    // ######################################## *** Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)

    public function IsChargeable()
    {
        return true;
    }

    // ######################################## *** standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        // we must check for individual database types here because each deals with schema in a none standard way
        $modifiers = PickUpOrDeliveryModifier::get()->filter(array("OptionID" => 0));
        if ($modifiers->count()) {
            DB::alteration_message("You need to upgrade PickUpOrDeliveryModifier <a href=\"/dev/tasks/EcommerceTaskUpgradePickUpOrDeliveryModifier\">do it now!</a>", "deleted");
        }
    }
    // ######################################## *** AJAX related functions
    /**
     *
     * @param Array $js javascript array
     * @return Array for AJAX JSON
     **/
    public function updateForAjax(array $js)
    {
        $js = parent::updateForAjax($js);
        $jsonOptions = array();
        $liveOptions = $this->LiveOptions();
        if ($liveOptions && $liveOptions->count()) {
            $optionsArray = $liveOptions->map('ID', 'Name');
            if ($optionsArray && !is_array($optionsArray)) {
                $optionsArray = $optionsArray->toArray();
            }
            if ($optionsArray && count($optionsArray)) {
                foreach ($optionsArray as $id => $name) {
                    $jsonOptions[] = array('id' => $id, 'name' => $name);
                }
            }
        }
        $js[] = array(
            't' => 'dropdown',
            's' => 'PickupOrDeliveryType',
            'p' => $this->LiveOptionID(),
            'v' => $jsonOptions
        );
        return $js;
    }

    // ######################################## *** debug functions
}
