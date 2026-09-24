<?php

namespace Sunnysideup\EcommerceDelivery\Extensions;

use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierAdditional;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;

/**
 * Class \Sunnysideup\EcommerceDelivery\Extensions\ProductDeliveryExtension
 *
 * @property \Sunnysideup\Ecommerce\Pages\Product|\Sunnysideup\EcommerceDelivery\Extensions\ProductDeliveryExtension $owner
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions[] UnavailableDeliveryOptions()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierAdditional[] AdditionalDeliveryCosts()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions[] ExcludedFromDeliveryCosts()
 */
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
        $mapOptions = PickUpOrDeliveryModifierOptions::get()->map('ID', 'Title')->toArray();
        $mapAdditional = PickUpOrDeliveryModifierAdditional::get()->map('ID', 'Title')->toArray();
        $fields->addFieldsToTab(
            'Root.Delivery',
            [
                CheckboxSetField::create(
                    'UnavailableDeliveryOptions',
                    'Unavailable Delivery Options',
                    $mapOptions
                ),
                CheckboxSetField::create(
                    'AdditionalDeliveryCosts',
                    'Additional',
                    $mapAdditional
                ),
                CheckboxSetField::create(
                    'ExcludedFromDeliveryCosts',
                    'Excluded from Delivery Costs for these options',
                    $mapOptions
                ),
            ]
        );

        return $fields;
    }
}
