<?php

namespace Sunnysideup\EcommerceDelivery\Model;

use SilverStripe\ORM\DataExtension;

/**
 * Class \Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptionsRegion
 *
 * @property \Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptionsRegion $owner
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions[] AvailableInRegions()
 */
class PickUpOrDeliveryModifierOptionsRegion extends DataExtension
{
    private static $belongs_many_many = [
        'AvailableInRegions' => PickUpOrDeliveryModifierOptions::class,
    ];
}
