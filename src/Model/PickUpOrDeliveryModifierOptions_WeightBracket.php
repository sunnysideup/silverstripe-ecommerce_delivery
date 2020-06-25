<?php

namespace Sunnysideup\EcommerceDelivery\Model;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

/**
 * below we record options for weight brackets with fixed cost
 * e.g. if Order.Weight > 10 and Order.Weight < 20 => Charge is $111.
 */
class PickUpOrDeliveryModifierOptions_WeightBracket extends DataObject
{
    /**
     * ### @@@@ START REPLACEMENT @@@@ ###
     * OLD: private static $db (case sensitive)
     * NEW:
    private static $db (COMPLEX)
     * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
     * ### @@@@ STOP REPLACEMENT @@@@ ###
     */
    private static $table_name = 'PickUpOrDeliveryModifierOptions_WeightBracket';

    private static $db = [
        'Name' => 'Varchar',
        'MinimumWeight' => 'Int',
        'MaximumWeight' => 'Int',
        'FixedCost' => 'Currency',
    ];

    private static $belongs_many_many = [
        'PickUpOrDeliveryModifierOptions' => PickUpOrDeliveryModifierOptions::class,
    ];

    private static $indexes = [
        'MinimumWeight' => true,
        'MaximumWeight' => true,
    ];

    private static $searchable_fields = [
        'Name' => 'PartialMatchFilter',
    ];

    private static $field_labels = [
        'Name' => 'Description (e.g. small parcel)',
        'MinimumWeight' => 'The minimum weight in grams',
        'MaximumWeight' => 'The maximum weight in grams',
        'FixedCost' => 'Total price (fixed cost)',
    ];

    private static $summary_fields = [
        'Name',
        'MinimumWeight',
        'MaximumWeight',
        'FixedCost',
    ];

    private static $singular_name = 'Weight Bracket';

    private static $plural_name = 'Weight Brackets';

    private static $default_sort = 'MinimumWeight ASC, MaximumWeight ASC';

    public function i18n_singular_name()
    {
        return _t('PickUpOrDeliveryModifierOptions.WEIGHTBRACKET', 'Weight Bracket');
    }

    public function i18n_plural_name()
    {
        return _t('PickUpOrDeliveryModifierOptions.WEIGHTBRACKETS', 'Weight Brackets');
    }

    /**
     * standard SS method
     * @param Member $member | NULL
     * @return boolean
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
     * @param Member $member | NULL
     * @return boolean
     */
    public function canView($member = null, $context = [])
    {
        return true;
    }

    /**
     * standard SS method
     * @param Member $member | NULL
     * @return boolean
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
     * @param Member $member | NULL
     * @return boolean
     */
    public function canDelete($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }
        return parent::canDelete($member);
    }
}
