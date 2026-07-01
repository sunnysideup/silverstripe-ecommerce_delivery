<?php

declare(strict_types=1);

namespace Sunnysideup\EcommerceDelivery\Model;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\ManyManyList;
/**
 * Class \Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptionsRegion
 *
 * @property PickUpOrDeliveryModifierOptionsRegion $owner
 * @method ManyManyList|PickUpOrDeliveryModifierOptions[] AvailableInRegions()
 */
class PickUpOrDeliveryModifierOptionsRegion extends Extension
{
    private static $belongs_many_many = [
        'AvailableInRegions' => PickUpOrDeliveryModifierOptions::class,
    ];
}
