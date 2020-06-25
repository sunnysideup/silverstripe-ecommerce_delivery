<?php

namespace Sunnysideup\EcommerceDelivery\Model;





use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
use SilverStripe\Core\Config\Config;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;




/**
 * below we record options for subTotal brackets with fixed cost
 * e.g. if Order.SubTotal > 10 and Order.SubTotal < 20 => Charge is $111.
 *
 *
 *
 */
class PickUpOrDeliveryModifierOptions_SubTotalBracket extends DataObject
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
    
    private static $table_name = 'PickUpOrDeliveryModifierOptions_SubTotalBracket';

    private static $db = array(
        "Name" => "Varchar",
        "MinimumSubTotal" => "Currency",
        "MaximumSubTotal" => "Currency",
        "FixedCost" => "Currency"
    );

    private static $belongs_many_many = array(
        "PickUpOrDeliveryModifierOptions" => PickUpOrDeliveryModifierOptions::class
    );

    private static $indexes = array(
        "MinimumSubTotal" => true,
        "MaximumSubTotal" => true
    );

    private static $searchable_fields = array(
        "Name" => "PartialMatchFilter"
    );

    private static $field_labels = array(
        "Name" => "Description (e.g. order below a hundy)",
        "MinimumSubTotal" => "The minimum Sub-Total for the Order",
        "MaximumSubTotal" => "The maximum Sub-Total for the Order",
        "FixedCost" => "Total price (fixed cost)"
    );

    private static $summary_fields = array(
        "Name",
        "MinimumSubTotal",
        "MaximumSubTotal",
        "FixedCost"
    );

    private static $singular_name = "Sub-Total Bracket";

    public function i18n_singular_name()
    {
        return _t("PickUpOrDeliveryModifierOptions.SUBTOTAL_BRACKET", "Sub-Total Bracket");
    }

    private static $plural_name = "SubTotal Brackets";

    public function i18n_plural_name()
    {
        return _t("PickUpOrDeliveryModifierOptions.SUBTOTAL_BRACKETS", "Sub-Total Brackets");
    }

    private static $default_sort = "MinimumSubTotal ASC, MaximumSubTotal ASC";


    /**
     * standard SS method
     * @param Member | NULL
     * @return Boolean
     */
    public function canCreate($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
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
        return true;
    }

    /**
     * standard SS method
     * @param Member | NULL
     * @return Boolean
     */
    public function canEdit($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
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
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
            return true;
        }
        return parent::canDelete($member);
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('Name', ReadonlyField::create('Name', 'Description'));
        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Name = 'MIN '.$this->MinimumSubTotal.' MAX '.$this->MaximumSubTotal. ', COST: '.$this->FixedCost;
    }
}

