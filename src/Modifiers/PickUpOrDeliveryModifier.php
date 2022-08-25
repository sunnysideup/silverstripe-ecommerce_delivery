<?php

namespace Sunnysideup\EcommerceDelivery\Modifiers;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\Validator;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
use Sunnysideup\Ecommerce\Model\Address\EcommerceRegion;
use Sunnysideup\Ecommerce\Model\OrderModifier;
use Sunnysideup\EcommerceDelivery\Forms\PickUpOrDeliveryModifierForm;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;

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
    /**
     * @var string
     *             Debugging tool
     */
    protected $debugMessage = '';

    private static $debug = false;

    // ######################################## *** model defining static variables (e.g. $db, $has_one)
    private static $table_name = 'PickUpOrDeliveryModifier';

    private static $db = [
        'TotalWeight' => 'Double',
        'RegionAndCountry' => 'Varchar',
        'SerializedCalculationObject' => 'Text',
        'DebugString' => 'HTMLText',
        'SubTotalAmount' => 'Currency',
    ];

    private static $defaults = [
        'Type' => 'Delivery',
    ];

    private static $has_one = [
        'Option' => PickUpOrDeliveryModifierOptions::class,
    ];

    private static $singular_name = 'Pickup / Delivery Charge';

    private static $plural_name = 'Pickup / Delivery Charges';

    private static $include_form_in_order_table = true;

    private static $use_dropdown_field = false;

    // ######################################## *** other (non) static variables (e.g. private static $special_name_for_something, protected $order)

    /**
     * @var string - the field used in the Buyable to work out the weight
     */
    private static $weight_field = 'Weight';

    /**
     * @var float
     *            the total amount of weight for the order
     *            saved here for speed's sake
     */
    private static $_total_weight;

    /**
     * @var DataList
     */
    private static $available_options;

    /**
     * @var PickUpOrDeliveryModifierOptions
     *                                      The most applicable option
     */
    private static $selected_option;

    /**
     * @var float
     *            the total amount charged in the end.
     *            saved here for speed's sake
     */
    private static $_actual_charges = 0;

    /**
     * @var bool
     *           the total amount charged in the end
     *           saved here for speed's sake
     */
    private static $calculations_done = false;

    public function i18n_singular_name()
    {
        return _t('PickUpOrDeliveryModifier.DELIVERYCHARGE', 'Delivery / Pick-up');
    }

    public function i18n_plural_name()
    {
        return _t('PickUpOrDeliveryModifier.DELIVERYCHARGES', 'Delivery / Pick-up');
    }

    // ######################################## *** cms variables + functions (e.g. getCMSFields, $searchableFields)

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        //debug fields
        $fields->removeByName('TotalWeight');
        $fields->addFieldToTab('Root.Debug', new ReadonlyField('TotalWeightShown', 'total weight used for calculation', $this->TotalWeight));
        $fields->removeByName('SubTotalAmount');
        $fields->addFieldToTab('Root.Debug', new ReadonlyField('SubTotalAmountShown', 'sub-total amount used for calculation', $this->SubTotalAmount));
        $fields->removeByName('SerializedCalculationObject');
        //careful, this causes errors!
        // $fields->addFieldToTab('Root.Debug', new ReadonlyField('SerializedCalculationObjectShown', 'debug data', unserialize($this->SerializedCalculationObject)));
        $fields->removeByName('DebugString');
        //careful, this causes errors!
        // $fields->addFieldToTab('Root.Debug', new ReadonlyField('DebugStringShown', 'steps taken', $this->DebugString));
        return $fields;
    }

    // ######################################## *** CRUD functions (e.g. canEdit)

    // ######################################## *** init and update functions

    /**
     * set the selected option (selected by user using form).
     *
     * @param int $optionID
     */
    public function setOption($optionID)
    {
        $optionID = (int) $optionID;
        $this->OptionID = $optionID;
        $this->write();
    }

    /**
     * updates database fields.
     *
     * @param bool $force - run it, even if it has run already
     */
    public function runUpdate($force = true)
    {
        if ($this->Config()->get('debug')) {
            $this->debugMessage = '';
        }

        self::$calculations_done = false;
        self::$selected_option = null;
        self::$available_options = null;
        $this->checkField('OptionID');
        $this->checkField('SerializedCalculationObject');
        $this->checkField('TotalWeight');
        $this->checkField('SubTotalAmount');
        $this->checkField('RegionAndCountry');
        $this->checkField('CalculatedTotal');
        if ($this->Config()->get('debug')) {
            $this->checkField('DebugString');
        }

        parent::runUpdate($force);
    }

    // ######################################## *** form functions (e. g. Showform and getform)

    /**
     * standard Modifier Method.
     */
    public function ShowForm(): bool
    {
        if ($this->ShowInTable()) {
            if ($this->getOrderCached()->Items()) {
                $options = $this->liveOptions();
                if ($options) {
                    return $options->limit(2)->count() > 1;
                }
            }
        }

        return false;
    }

    /**
     * Should the form be included in the editable form
     * on the checkout page?
     */
    public function ShowFormInEditableOrderTable(): bool
    {
        return $this->ShowForm() && $this->Config()->get('include_form_in_order_table');
    }

    /**
     * @return \SilverStripe\Forms\Form
     */
    public function getModifierForm(Controller $optionalController = null, Validator $optionalValidator = null)
    {
        /*
         * ### @@@@ START REPLACEMENT @@@@ ###
         * WHY doesnt this work?
         */
        Requirements::themedCSS('client/css/PickUpOrDeliveryModifier');

        Requirements::javascript('silverstripe/admin: thirdparty/jquery/jquery.js');
        //Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
        //Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
        Requirements::javascript('silverstripe/admin: thirdparty/jquery/jquery.js');
        Requirements::javascript('sunnysideup/ecommerce_delivery: client/javascript/PickUpOrDeliveryModifier.js');
        $array = PickUpOrDeliveryModifierOptions::get_all_as_country_array();
        if ($array && is_array($array) && count($array)) {
            $js = "\n" . 'var PickUpOrDeliveryModifierOptions = []';
            $count = 0;
            foreach ($array as $key => $option) {
                if ($option && is_array($option) && count($option)) {
                    $js .= "\n" . '    PickUpOrDeliveryModifierOptions["' . $key . '"] = new Array("' . implode('","', $option) . '")';
                    ++$count;
                }
            }

            if ($js) {
                //add final semi-comma
                $js .= '';
                Requirements::customScript($js, 'PickUpOrDeliveryModifier');
            }
        }

        $fields = new FieldList();
        $fields->push($this->headingField());
        $fields->push($this->descriptionField());

        $options = $this->liveOptions()->map('ID', 'Name'); //$this->getOptionListForDropDown();
        $optionID = $this->LiveOptionID();

        if ($this->Config()->get('use_dropdown_field')) {
            $fields->push(DropdownField::create('PickupOrDeliveryType', 'Preference', $options, $optionID));
        } else {
            $fields->push(OptionsetField::create('PickupOrDeliveryType', 'Preference', $options, $optionID));
        }

        $actions = new FieldList(
            new FormAction('processOrderModifier', 'Update Pickup / Delivery Option')
        );

        return new PickUpOrDeliveryModifierForm($optionalController, 'PickUpOrDeliveryModifier', $fields, $actions, $optionalValidator);
    }

    // ######################################## *** template functions (e.g. ShowInTable, TableTitle, etc...) ... USES DB VALUES

    public function ShowInTable(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function CanBeRemoved()
    {
        return false;
    }

    /**
     * NOTE: the function below is  HACK and needs fixing proper.
     */
    public function CartValue()
    {
        return $this->getCartValue();
    }

    public function getCartValue()
    {
        return $this->LiveCalculatedTotal();
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
        $modifiers = PickUpOrDeliveryModifier::get()->filter(['OptionID' => 0]);
        if ($modifiers->exists()) {
            DB::alteration_message('You need to upgrade PickUpOrDeliveryModifier <a href="/dev/tasks/EcommerceTaskUpgradePickUpOrDeliveryModifier">do it now!</a>', 'deleted');
        }
    }

    // ######################################## *** AJAX related functions

    /**
     * @param array $js javascript array
     *
     * @return array for AJAX JSON
     */
    public function updateForAjax(array $js)
    {
        $js = parent::updateForAjax($js);
        $jsonOptions = [];
        $liveOptions = $this->LiveOptions();
        if ($liveOptions->exists()) {
            $optionsArray = $liveOptions->map('ID', 'Name');
            if ($optionsArray && ! is_array($optionsArray)) {
                $optionsArray = $optionsArray->toArray();
            }

            if ($optionsArray && count($optionsArray)) {
                foreach ($optionsArray as $id => $name) {
                    $jsonOptions[] = ['id' => $id, 'name' => $name];
                }
            }
        }

        $js[] = [
            't' => 'dropdown',
            's' => 'PickupOrDeliveryType',
            'p' => $this->LiveOptionID(),
            'v' => $jsonOptions,
        ];

        return $js;
    }

    // ######################################## ***  inner calculations.... USES CALCULATED VALUES

    /**
     * returns the current selected option as object.
     *
     * @return PickUpOrDeliveryModifierOptions;
     */
    protected function LiveOptionObject()
    {
        return PickUpOrDeliveryModifierOptions::get_by_id($this->LiveOptionID());
    }

    /**
     * works out if Weight is applicable at all.
     *
     * @return bool
     */
    protected function useWeight()
    {
        return EcommerceConfig::inst()->ProductsHaveWeight;
    }

    /**
     * Returns the available delivery options based on the current country and region
     * for the order.
     * Must always return something!
     *
     * @return \SilverStripe\ORM\ArrayList
     */
    protected function LiveOptions()
    {
        if (! self::$available_options) {
            $results = [];
            $countryID = EcommerceCountry::get_country_id();
            $regionID = EcommerceRegion::get_region_id();
            $options = PickUpOrDeliveryModifierOptions::get();
            if ($options->exists()) {
                foreach ($options as $option) {
                    //check countries
                    if ($countryID) {
                        $availableInCountriesList = $option->AvailableInCountries();
                        //exclude if not found in country list
                        if (
                            $availableInCountriesList->exists() &&
                            ! $availableInCountriesList->filter('ID', $countryID)->exists()
                        ) {
                            continue;
                        }

                        //exclude if in exclusion list
                        $excludedFromCountryList = $option->ExcludeFromCountries();
                        if (
                            $excludedFromCountryList->exists() &&
                            $excludedFromCountryList->filter('ID', $countryID)->exists()
                        ) {
                            continue;
                        }
                    }

                    //check regions
                    if ($regionID) {
                        $optionRegions = $option->AvailableInRegions();
                        //exclude if not found in region list
                        if (
                            $optionRegions->exists() &&
                            ! $optionRegions->filter(['ID' => $regionID])->exists()
                        ) {
                            continue;
                        }
                    }

                    $results[] = $option;
                }
            }

            if (! isset($results)) {
                $results[] = PickUpOrDeliveryModifierOptions::default_object();
            }
            $extended = $this->extend('LiveOptionExtension', $results);
            if($extended !== null) {
                $results = $extended;
            }

            self::$available_options = new ArrayList($results);
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
     *
     * @return int
     */
    protected function LiveOptionID()
    {
        if (! self::$selected_option) {
            $options = $this->liveOptions();
            self::$selected_option = $options->filter(['ID' => $this->OptionID])->first();
            if (self::$selected_option) {
                //do nothing;
            } else {
                self::$selected_option = $options->filter(['IsDefault' => 1])->first();
                if (! self::$selected_option) {
                    self::$selected_option = $options->first();
                }
            }
        }

        return self::$selected_option->ID;
    }

    /**
     * @return string
     */
    protected function LiveName()
    {
        $obj = $this->liveOptionObject();
        if (is_object($obj)) {
            $v = $obj->Name;
            if ($obj->ExplanationPageID) {
                $page = $obj->ExplanationPage();
                if ($page) {
                    $v .= '<div id="PickUpOrDeliveryModifierExplanationLink"><a href="' . $page->Link() . '" class="externalLink">' . Convert::raw2sql($page->Title) . '</a></div>';
                }
            }

            return $v;
        }

        return _t('PickUpOrDeliveryModifier.POSTAGEANDHANDLING', 'Postage and Handling');
    }

    /**
     * cached in Order, no need to cache here.
     *
     * @return float
     */
    protected function LiveSubTotalAmount()
    {
        $order = $this->getOrderCached();

        return $order->SubTotal();
    }

    /**
     * description of region and country being shipped to.
     *
     * @return null|PickUpOrDeliveryModifierOptions
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
     */
    protected function LiveRegionAndCountry(): string
    {
        $details = [];
        $option = $this->Option();
        if ($option) {
            $regionID = EcommerceRegion::get_region_id();
            if ($regionID) {
                $region = EcommerceRegion::get_by_id($regionID);
                if ($region) {
                    $details[] = $region->Name;
                }
            }

            $countryID = EcommerceCountry::get_country_id();
            if ($countryID) {
                $country = EcommerceCountry::get_by_id($countryID);
                if ($country) {
                    $details[] = $country->Name;
                }
            }
        } else {
            return _t('PickUpOrDeliveryModifier.NOTSELECTED', 'No delivery option has been selected');
        }

        if (count($details)) {
            return implode(', ', $details);
        }

        return '';
    }

    /**
     * @return float
     */
    protected function LiveCalculatedTotal()
    {
        //________________ start caching mechanism
        if (self::$calculations_done) {
            return self::$_actual_charges;
        }

        self::$calculations_done = true;
        //________________ end caching mechanism

        self::$_actual_charges = 0;
        $fixedPriceExtra = 0;
        //do we have enough information
        $obj = $this->liveOptionObject();
        $items = $this->getOrderCached()->Items();
        if (is_object($obj) && $obj->exists() && $items->exists()) {
            //are ALL products excluded?
            if ($obj->ExcludedProducts()->exists()) {
                $hasIncludedProduct = false;
                $excludedProductIDArray = $obj->ExcludedProducts()->columnUnique();
                //are all the products excluded?
                foreach ($items as $orderItem) {
                    $product = $orderItem->Product();
                    if ($product) {
                        if (in_array($product->ID, $excludedProductIDArray, true)) {
                            //do nothing
                        } else {
                            $hasIncludedProduct = true;

                            break;
                        }
                    }
                }

                if (false === $hasIncludedProduct) {
                    if ($this->Config()->get('debug')) {
                        $this->debugMessage .= '<hr />all products are excluded from delivery charges';
                    }

                    return self::$_actual_charges;
                }
            }

            $productsIds = $this->getOrderCached()->Items()->columnUnique('BuyableID');
            // $productsWithQuantity = $this->getOrderCached()->Items()->map('BuyableID', 'Quantity');
            // $productsIds = array_keys($productsWithQuantity);
            if (is_array($productsIds) && count($productsIds)) {
                if ($this->Config()->get('debug')) {
                    $this->debugMessage .= '<hr />found products: ' . implode(',', $productsIds);
                }

                if ($obj->AdditionalCostForSpecificProducts()->exists()) {
                    if ($this->Config()->get('debug')) {
                        $this->debugMessage .= '<hr />found additional costs options';
                    }

                    foreach ($obj->AdditionalCostForSpecificProducts() as $addExtras) {
                        if ($this->Config()->get('debug')) {
                            $this->debugMessage .= '<hr />additional cost centre: ' . $addExtras->Title;
                        }

                        $testProducts = $addExtras->IncludedProducts()->columnUnique();
                        if (is_array($testProducts) && count($testProducts)) {
                            if ($this->Config()->get('debug')) {
                                $this->debugMessage .= '<hr />found test products: ' . implode(',', $testProducts);
                            }

                            $intersect = array_intersect($productsIds, $testProducts);
                            $countItems = count($intersect);
                            if ($countItems) {
                                $fixedPriceExtra += ($countItems * $addExtras->FixedCost);
                            }
                        }
                    }
                }
            }

            if ($this->Config()->get('debug')) {
                $this->debugMessage .= '<hr />option selected: ' . $obj->Title . ', and items present';
            }

            //lets check sub-total
            $subTotalAmount = $this->LiveSubTotalAmount();
            if ($this->Config()->get('debug')) {
                $this->debugMessage .= '<hr />sub total amount is: $' . $subTotalAmount;
            }

            // no need to charge, order is big enough
            $minForZeroRate = floatval($obj->MinimumOrderAmountForZeroRate);
            $maxForZeroRate = floatval($obj->FreeShippingUpToThisOrderAmount);
            $weight = $this->LiveTotalWeight();
            $weightBrackets = $obj->WeightBrackets();
            $subTotalBrackets = $obj->SubTotalBrackets();
            // zero becauase over minForZeroRate
            if ($minForZeroRate > 0 && $minForZeroRate < $subTotalAmount) {
                self::$_actual_charges = 0;
                if ($this->Config()->get('debug')) {
                    $this->debugMessage .= '<hr />Minimum Order Amount For Zero Rate: ' . $obj->MinimumOrderAmountForZeroRate . ' is lower than amount  ordered: ' . self::$_actual_charges;
                }
            } elseif ($maxForZeroRate > 0 && $maxForZeroRate > $subTotalAmount) {
                //zero because below maxForZeroRate
                self::$_actual_charges = 0;
                if ($this->Config()->get('debug')) {
                    $this->debugMessage .= '<hr />Maximum Order Amount For Zero Rate: ' . $obj->FreeShippingUpToThisOrderAmount . ' is higher than amount ordered: ' . self::$_actual_charges;
                }
            } else {
                //examine weight brackets
                if ($weight && $weightBrackets->exists()) {
                    if ($this->Config()->get('debug')) {
                        $this->debugMessage .= "<hr />there is weight: {$weight}gr.";
                    }

                    //weight brackets
                    $foundWeightBracket = null;
                    $weightBracketQuantity = 1;
                    $additionalWeightBracket = null;
                    $minimumMinimum = null;
                    $maximumMaximum = null;
                    foreach ($weightBrackets as $weightBracket) {
                        if (! $foundWeightBracket && ($weightBracket->MinimumWeight <= $weight) && ($weight <= $weightBracket->MaximumWeight)) {
                            $foundWeightBracket = $weightBracket;
                        }

                        //look for absolute min and max
                        if (null === $minimumMinimum || ($weightBracket->MinimumWeight > $minimumMinimum->MinimumWeight)) {
                            $minimumMinimum = $weightBracket;
                        }

                        if (null === $maximumMaximum || ($weightBracket->MaximumWeight > $maximumMaximum->MaximumWeight)) {
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
                        if ($this->Config()->get('debug')) {
                            $this->debugMessage .= "<hr />found Weight Bracket (from {$foundWeightBracket->MinimumWeight}gr. to {$foundWeightBracket->MaximumWeight}gr.): \${$foundWeightBracket->FixedCost} ({$foundWeightBracket->Name}) from  times {$weightBracketQuantity}";
                        }

                        if ($additionalWeightBracket) {
                            self::$_actual_charges += $additionalWeightBracket->FixedCost;
                            if ($this->Config()->get('debug')) {
                                $this->debugMessage .= "<hr />+ additional Weight Bracket (from {$additionalWeightBracket->MinimumWeight}gr. to {$additionalWeightBracket->MaximumWeight}gr.): \${$additionalWeightBracket->FixedCost} ({$foundWeightBracket->Name})";
                            }
                        }
                    }
                } elseif ($weight && $obj->WeightMultiplier) {
                    // weight based on multiplier ...
                    // add weight based shipping
                    if (! $obj->WeightUnit) {
                        $obj->WeightUnit = 1;
                    }

                    if ($this->Config()->get('debug')) {
                        $this->debugMessage .= '<hr />actual weight:' . $weight . ' multiplier = ' . $obj->WeightMultiplier . ' weight unit = ' . $obj->WeightUnit . ' ';
                    }

                    //legacy fix
                    $units = ceil($weight / $obj->WeightUnit);
                    $weightCharge = $units * $obj->WeightMultiplier;
                    self::$_actual_charges += $weightCharge;
                    if ($this->Config()->get('debug')) {
                        $this->debugMessage .= '<hr />weight charge: ' . $weightCharge;
                    }
                } elseif ($subTotalAmount && $subTotalBrackets->exists()) {
                    //examine price brackets
                    if ($this->Config()->get('debug')) {
                        $this->debugMessage .= "<hr />there is subTotal: {$subTotalAmount} and subtotal brackets.";
                    }

                    //subTotal brackets
                    $foundSubTotalBracket = null;
                    foreach ($subTotalBrackets as $subTotalBracket) {
                        if (! $foundSubTotalBracket && ($subTotalBracket->MinimumSubTotal <= $subTotalAmount) && ($subTotalAmount <= $subTotalBracket->MaximumSubTotal)) {
                            $foundSubTotalBracket = $subTotalBracket;

                            break;
                        }
                    }

                    //we found some applicable subTotal brackets
                    if ($foundSubTotalBracket) {
                        self::$_actual_charges += $foundSubTotalBracket->FixedCost;
                        if ($this->Config()->get('debug')) {
                            $this->debugMessage .= "<hr />found SubTotal Bracket (between {$foundSubTotalBracket->MinimumSubTotal} and {$foundSubTotalBracket->MaximumSubTotal}): \${$foundSubTotalBracket->FixedCost} ({$foundSubTotalBracket->Name}) ";
                        }
                    }
                }

                // add percentage
                if ($obj->Percentage) {
                    $percentageCharge = $subTotalAmount * $obj->Percentage;
                    self::$_actual_charges += $percentageCharge;
                    if ($this->Config()->get('debug')) {
                        $this->debugMessage .= '<hr />percentage charge: $' . $percentageCharge;
                    }
                }

                // add fixed price
                if (0 !== $obj->FixedCost) {
                    self::$_actual_charges += $obj->FixedCost;
                    if ($this->Config()->get('debug')) {
                        $this->debugMessage .= '<hr />fixed charge: $' . $obj->FixedCost;
                    }
                }
            }

            //is it enough?
            if (self::$_actual_charges < $obj->MinimumDeliveryCharge && $obj->MinimumDeliveryCharge > 0) {
                $oldActualCharge = self::$_actual_charges;
                self::$_actual_charges = $obj->MinimumDeliveryCharge;
                if ($this->Config()->get('debug')) {
                    $this->debugMessage .= '<hr />too little: actual charge: ' . $oldActualCharge . ', minimum delivery charge: ' . $obj->MinimumDeliveryCharge;
                }
            }

            // is it too much
            if (self::$_actual_charges > $obj->MaximumDeliveryCharge && $obj->MaximumDeliveryCharge > 0) {
                self::$_actual_charges = $obj->MaximumDeliveryCharge;
                if ($this->Config()->get('debug')) {
                    $this->debugMessage .= '<hr />too much: ' . self::$_actual_charges . ', maximum delivery charge is ' . $obj->MaximumDeliveryCharge;
                }
            }

            if ($fixedPriceExtra) {
                self::$_actual_charges = $fixedPriceExtra;
                if ($this->Config()->get('debug')) {
                    $this->debugMessage .= '<hr />setting to fixed charges of: ' . $fixedPriceExtra;
                }
            }
        } elseif (! $items) {
            if ($this->Config()->get('debug')) {
                $this->debugMessage .= '<hr />no items present';
            }
        } elseif ($this->Config()->get('debug')) {
            $this->debugMessage .= '<hr />no delivery option available';
        }

        if ($this->Config()->get('debug')) {
            $this->debugMessage .= '<hr />final score: $' . self::$_actual_charges;
        }

        // echo $this->debugMessage;
        //special case, we are using weight and there is no weight!
        return self::$_actual_charges;
    }

    /**
     * @return float
     */
    protected function LiveTotalWeight()
    {
        if (null === self::$_total_weight) {
            self::$_total_weight = 0;
            if ($this->useWeight()) {
                $fieldName = Config::inst()->get(PickUpOrDeliveryModifier::class, 'weight_field');
                if ($fieldName) {
                    $items = $this->getOrderCached()->Items();
                    //get index numbers for bonus products - this can only be done now once they have actually been added
                    if ($items && $items->exists()) {
                        foreach ($items as $item) {
                            $buyable = $item->getBuyableCached();
                            if ($buyable) {
                                // Calculate the total weight of the order
                                if (! empty($buyable->{$fieldName}) && $item->Quantity) {
                                    self::$_total_weight += $buyable->{$fieldName} * $item->Quantity;
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
     *
     * @return string
     */
    protected function LiveDebugString()
    {
        return $this->debugMessage;
    }

    // ######################################## *** debug functions
}
