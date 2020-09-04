<?php

namespace Sunnysideup\EcommerceDelivery\Model;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

/**
 * below we record options for subTotal brackets with fixed cost
 * e.g. if Order.SubTotal > 10 and Order.SubTotal < 20 => Charge is $111.
 */
class PickUpOrDeliveryModifierOptionsSubTotalBracket extends DataObject
{
    private static $table_name = 'PickUpOrDeliveryModifierOptionsSubTotalBracket';

    private static $db = [
        'Name' => 'Varchar',
        'MinimumSubTotal' => 'Currency',
        'MaximumSubTotal' => 'Currency',
        'FixedCost' => 'Currency',
    ];

    private static $belongs_many_many = [
        'PickUpOrDeliveryModifierOptions' => PickUpOrDeliveryModifierOptions::class,
    ];

    private static $indexes = [
        'MinimumSubTotal' => true,
        'MaximumSubTotal' => true,
    ];

    private static $searchable_fields = [
        'Name' => 'PartialMatchFilter',
    ];

    private static $field_labels = [
        'Name' => 'Description (e.g. order below a hundy)',
        'MinimumSubTotal' => 'The minimum Sub-Total for the Order',
        'MaximumSubTotal' => 'The maximum Sub-Total for the Order',
        'FixedCost' => 'Total price (fixed cost)',
    ];

    private static $summary_fields = [
        'Name',
        'MinimumSubTotal',
        'MaximumSubTotal',
        'FixedCost',
    ];

    private static $singular_name = 'Sub-Total Bracket';

    private static $plural_name = 'SubTotal Brackets';

    private static $default_sort = 'MinimumSubTotal ASC, MaximumSubTotal ASC';

    public function i18n_singular_name()
    {
        return _t('PickUpOrDeliveryModifierOptions.SUBTOTAL_BRACKET', 'Sub-Total Bracket');
    }

    public function i18n_plural_name()
    {
        return _t('PickUpOrDeliveryModifierOptions.SUBTOTAL_BRACKETS', 'Sub-Total Brackets');
    }

    /**
     * standard SS method
     * @param \SilverStripe\Security\Member $member | NULL
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }
        return parent::canCreate($member);
    }

    /**
     * standard SS method
     * @param \SilverStripe\Security\Member $member | NULL
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        return true;
    }

    /**
     * standard SS method
     * @param \SilverStripe\Security\Member $member | NULL
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }
        return parent::canEdit($member);
    }

    /**
     * standard SS method
     * @param \SilverStripe\Security\Member $member | NULL
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }
        return parent::canDelete($member);
    }

    /**
     * CMS Fields
     * @return \ SilverStripe\Forms\FieldList
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
        $this->Name = 'MIN ' . $this->MinimumSubTotal . ' MAX ' . $this->MaximumSubTotal . ', COST: ' . $this->FixedCost;
    }
}
