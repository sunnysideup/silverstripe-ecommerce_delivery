<?php

namespace Sunnysideup\EcommerceDelivery\Forms;

use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Forms\OrderModifierForm;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
use Sunnysideup\EcommerceDelivery\Modifiers\PickUpOrDeliveryModifier;

class PickUpOrDeliveryModifier_Form extends OrderModifierForm
{
    public function processOrderModifier($data, $form = null)
    {
        if (isset($data['PickupOrDeliveryType'])) {
            $newOption = intval($data['PickupOrDeliveryType']);
            $newOptionObj = PickUpOrDeliveryModifierOptions::get()->byID($newOption);
            if ($newOptionObj) {
                $order = ShoppingCart::current_order();
                if ($order) {
                    if ($modifiers = $order->Modifiers(PickUpOrDeliveryModifier::class)) {
                        foreach ($modifiers as $modifier) {
                            $modifier->setOption($newOption);
                            $modifier->runUpdate();
                        }
                        return ShoppingCart::singleton()->setMessageAndReturn(_t('PickUpOrDeliveryModifier.UPDATED', 'Delivery option updated'), 'good');
                    }
                }
            }
        }
        return ShoppingCart::singleton()->setMessageAndReturn(_t('PickUpOrDeliveryModifier.UPDATED', 'Delivery option could NOT be updated'), 'bad');
    }
}
