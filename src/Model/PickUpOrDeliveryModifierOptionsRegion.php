<?php

namespace Sunnysideup\EcommerceDelivery\Model;


use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
use SilverStripe\ORM\DataExtension;



/**
 *@author nicolaas [at] sunnysideup.co.nz
 *
 **/

class PickUpOrDeliveryModifierOptionsRegion extends DataExtension
{
    private static $belongs_many_many = array(
        "AvailableInRegions" => PickUpOrDeliveryModifierOptions::class
    );
}

