<?php

declare(strict_types=1);

namespace Sunnysideup\EcommerceDelivery\Extensions;

use SilverStripe\Core\Extension;
use Sunnysideup\Ecommerce\Pages\Product;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierAdditional;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;

/**
 * Class \Sunnysideup\EcommerceDelivery\Extensions\ProductDeliveryExtension
 *
 * @property Product|ProductDeliveryExtension $owner
 * @method ManyManyList|PickUpOrDeliveryModifierOptions[] UnavailableDeliveryOptions()
 * @method ManyManyList|PickUpOrDeliveryModifierAdditional[] AdditionalDeliveryCosts()
 * @method ManyManyList|PickUpOrDeliveryModifierOptions[] ExcludedFromDeliveryCosts()
 */
class ProductDeliveryExtension extends Extension
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
        $map = PickUpOrDeliveryModifierAdditional::get()->map('ID', 'TitleNice')->toArray();
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
