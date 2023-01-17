<?php

namespace Sunnysideup\EcommerceDelivery\Extensions;

use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierAdditional;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;

class ProductDeliveryExtension extends DataExtension
{
    private static $many_many = [
        'UnavailableDeliveryOptions' => PickUpOrDeliveryModifierOptions::class,
    ];

    private static $belongs_many_many = [
        'AdditionalDeliveryCosts' => PickUpOrDeliveryModifierAdditional::class,
        'ExcludedFromDeliveryCosts' => PickUpOrDeliveryModifierOptions::class,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;
        $map = PickUpOrDeliveryModifierAdditional::get()->map('ID', 'TitleNice');
        $fields->addFieldsToTab(
            'Root.Delivery',
            [
                CheckboxSetField::create(
                    'UnavailableDeliveryOptions',
                    'Unavailable Delivery Options',
                    $map
                ),
                CheckboxSetField::create(
                    'AdditionalDeliveryCosts',
                    'Additional',
                    $map
                ),
                CheckboxSetField::create(
                    'ExcludedFromDeliveryCosts',
                    'Excluded from',
                    $map
                ),
            ]
        );

        return $fields;
    }
}
