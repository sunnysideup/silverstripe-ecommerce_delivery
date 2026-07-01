<?php

namespace Sunnysideup\EcommerceDelivery\Model;

use SilverStripe\Core\Extension;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;

/**
 * Class \Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptionsCountry
 *
 * @property EcommerceCountry|PickUpOrDeliveryModifierOptionsCountry $owner
 * @method ManyManyList|PickUpOrDeliveryModifierOptions[] ExcludeFromCountries()
 * @method ManyManyList|PickUpOrDeliveryModifierOptions[] AvailableInCountries()
 */
class PickUpOrDeliveryModifierOptionsCountry extends Extension
{
    private static $belongs_many_many = [
        'AvailableInCountries' => PickUpOrDeliveryModifierOptions::class,
    ];

    private static $many_many = [
        'ExcludeFromCountries' => PickUpOrDeliveryModifierOptions::class,
    ];

    /**
     * Update Fields.
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeFieldFromTab('Root', 'AvailableInCountries');
        $fields->removeFieldFromTab('Root', 'ExcludeFromCountries');
        $fields->addFieldsToTab(
            'Root.Delivery',
            [
                GridField::create('AvailableInCountries', 'Included', $this->getOwner()->AvailableInCountries(), GridFieldConfig_RelationEditor::create()),
                GridField::create('ExcludeFromCountries', 'Excluded', $this->getOwner()->ExcludeFromCountries(), GridFieldConfig_RelationEditor::create()),
            ]
        );
    }
}
