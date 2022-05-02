<?php

namespace Sunnysideup\EcommerceDelivery\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Fields\OptionalTreeDropdownField;
use Sunnysideup\Ecommerce\Forms\Gridfield\Configs\GridFieldConfigForProducts;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
use Sunnysideup\Ecommerce\Model\Address\EcommerceRegion;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * @author nicolaas [at] sunnysideup.co.nz
 * Precondition : There can only be 1 default option
 */
class PickUpOrDeliveryModifierAdditional extends DataObject
{
    private static $table_name = 'PickUpOrDeliveryModifierAdditional';

    private static $db = [
        'Title' => 'Varchar',
        'FixedCost' => 'Currency',
        'Sort' => 'Int',
    ];

    private static $has_one = [
        'ExplanationPage' => SiteTree::class,
        'AddedWithOption' => PickUpOrDeliveryModifierOptions::class,
    ];

    private static $many_many = [
        'IncludedProducts' => Product::class,
    ];

    private static $searchable_fields = [
        'Title' => 'PartialMatchFilter',
    ];

    private static $field_labels = [
    ];

    private static $field_labels_right = [
    ];

    private static $defaults = [
    ];

    private static $summary_fields = [
        'Title',
        'FixedCosts',
    ];

    private static $casting = [
        'IsDefaultNice' => 'Varchar',
        'ListOfCountries' => 'Varchar',
    ];

    private static $singular_name = 'Additional Delivery Cost';

    private static $plural_name = 'Additional Delivery Costs';

    private static $default_sort = '"Sort" ASC, "Title" ASC';

    /**
     * standard SS method.
     *
     * @param null|mixed $member
     * @param mixed      $context
     *
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
     * standard SS method.
     *
     * @param null|mixed $member
     * @param mixed      $context
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
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
    public function canDelete($member = null)
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canDelete($member);
    }

}
