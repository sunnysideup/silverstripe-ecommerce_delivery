<?php

namespace Sunnysideup\EcommerceDelivery\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Forms\Gridfield\Configs\GridFieldConfigForProducts;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
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
    //
    // private static $field_labels = [
    // ];
    //
    // private static $field_labels_right = [
    // ];
    //
    // private static $defaults = [
    // ];

    private static $summary_fields = [
        'Title' => 'Name',
        'FixedCost' => 'Cost',
        'AddedWithOption.Title' => 'Delivery option',
        'IncludedProducts.Count' => 'Number of products',
    ];

    private static $casting = [
        'TitleNice' => 'Varchar',
    ];

    private static $singular_name = 'Additional Delivery Cost';

    private static $plural_name = 'Additional Delivery Costs';

    private static $default_sort = ['Sort' => 'ASC'];

    public function TitleNice()
    {
        return $this->getTitleNice();
    }

    public function getTitleNice()
    {
        return $this->Title . ' - $' . $this->FixedCost;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Sort');
        $fields->replaceField('ExplanationPageID', new TreeDropdownField($name = 'ExplanationPageID', $title = 'Explanation Page', SiteTree::class));

        $fields->replaceField(
            'IncludedProducts',
            $excludedProdsField = GridField::create(
                'IncludedProducts',
                'Included Products',
                $this->IncludedProducts(),
                GridFieldConfigForProducts::create()
            )
        );

        return $fields;
    }

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
