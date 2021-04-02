<?php

namespace Sunnysideup\EcommerceDelivery\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataExtension;

/**
 *@author nicolaas [at] sunnysideup.co.nz
 *
 **/

class PickUpOrDeliveryModifierOptionsCountry extends DataExtension
{
    private static $belongs_many_many = [
        'AvailableInCountries' => PickUpOrDeliveryModifierOptions::class,
    ];

    private static $many_many = [
        'ExcludeFromCountries' => PickUpOrDeliveryModifierOptions::class,
    ];

    /**
     * Update Fields
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
                    $this->owner->AvailableInCountries(),
                    GridFieldConfig_RelationEditor::create()
                ),
                new GridField(
                    'ExcludeFromCountries',
                    'Excluded',
                    $this->owner->ExcludeFromCountries(),
                    GridFieldConfig_RelationEditor::create()
                ),
            ]
        );
    }
}
