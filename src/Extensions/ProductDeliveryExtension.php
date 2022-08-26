<?php

namespace Sunnysideup\EcommerceDelivery\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\ORM\DataExtension;
use Sunnysideup\EcommerceDiscountCoupon\Modifiers\DiscountCouponModifier;

use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierAdditional;

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
        $map = PickUpOrDeliveryModifierAdditional::get()->map();
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
