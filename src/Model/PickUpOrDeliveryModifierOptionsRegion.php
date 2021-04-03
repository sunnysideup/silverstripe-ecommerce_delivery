<?php

namespace Sunnysideup\EcommerceDelivery\Model;

use SilverStripe\ORM\DataExtension;

/**
 *@author nicolaas [at] sunnysideup.co.nz
 */
class PickUpOrDeliveryModifierOptionsRegion extends DataExtension
{
    private static $belongs_many_many = [
        'AvailableInRegions' => PickUpOrDeliveryModifierOptions::class,
    ];
}
