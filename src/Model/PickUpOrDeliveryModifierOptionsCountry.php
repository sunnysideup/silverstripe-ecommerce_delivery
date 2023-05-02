<?php

namespace Sunnysideup\EcommerceDelivery\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataExtension;

/**
 * Class \Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptionsCountry
 *
 * @property \Sunnysideup\Ecommerce\Model\Address\EcommerceCountry|\Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptionsCountry $owner
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions[] ExcludeFromCountries()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions[] AvailableInCountries()
 */
class PickUpOrDeliveryModifierOptionsCountry extends DataExtension
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
                new GridField(
                    'AvailableInCountries',
                    'Included',
                    $this->getOwner()->AvailableInCountries(),
                    GridFieldConfig_RelationEditor::create()
                ),
                new GridField(
                    'ExcludeFromCountries',
                    'Excluded',
                    $this->getOwner()->ExcludeFromCountries(),
                    GridFieldConfig_RelationEditor::create()
                ),
            ]
        );
    }
}
