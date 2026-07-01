<?php

namespace Sunnysideup\EcommerceDelivery\Model;

use Override;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

/**
 * below we record options for subTotal brackets with fixed cost
 * e.g. if Order.SubTotal > 10 and Order.SubTotal < 20 => Charge is $111.
 *
 * @property string $Name
 * @property float $MinimumSubTotal
 * @property float $MaximumSubTotal
 * @property float $FixedCost
 * @method ManyManyList|PickUpOrDeliveryModifierOptions[] PickUpOrDeliveryModifierOptions()
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

    #[Override]
    public function i18n_singular_name()
    {
        return _t('PickUpOrDeliveryModifierOptions.SUBTOTAL_BRACKET', 'Sub-Total Bracket');
    }

    #[Override]
    public function plural_name()
    {
        return _t('PickUpOrDeliveryModifierOptions.SUBTOTAL_BRACKETS', 'Sub-Total Brackets');
    }

    /**
     * standard SS method.
     *
     * @param null|mixed $member
     * @param mixed      $context
     *
     * @return bool
     */
    #[Override]
    public function canCreate($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canCreate($member);
    }

    /**
     * standard SS method.
     *
     * @param null|mixed $member
     * @param mixed      $context
     *
     * @return bool
     */
    #[Override]
    public function canView($member = null, $context = [])
    {
        return true;
    }

    /**
     * standard SS method.
     *
     * @param null|mixed $member
     * @param mixed      $context
     *
     * @return bool
     */
    #[Override]
    public function canEdit($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * standard SS method.
     *
     * @param null|mixed $member
     *
     * @return bool
     */
    #[Override]
    public function canDelete($member = null)
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canDelete($member);
    }

    /**
     * CMS Fields.
     *
     * @return FieldList
     */
    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('Name', ReadonlyField::create('Name', 'Description'));

        return $fields;
    }

    #[Override]
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Name = 'MIN ' . $this->MinimumSubTotal . ' MAX ' . $this->MaximumSubTotal . ', COST: ' . $this->FixedCost;
    }
}
