<?php

namespace Sunnysideup\EcommerceDelivery\Forms;

use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Forms\OrderModifierForm;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
use Sunnysideup\EcommerceDelivery\Modifiers\PickUpOrDeliveryModifier;

class PickUpOrDeliveryModifierForm extends OrderModifierForm
{
    public function processOrderModifier($data, $form = null)
    {
        if (isset($data['PickupOrDeliveryType'])) {
            $newOption = (int) $data['PickupOrDeliveryType'];
            $newOptionObj = PickUpOrDeliveryModifierOptions::get_by_id($newOption);
            if ($newOptionObj) {
                $order = ShoppingCart::current_order();
                if ($order) {
                    $modifiers = $order->Modifiers(PickUpOrDeliveryModifier::class);
                    if ($modifiers) {
                        foreach ($modifiers as $modifier) {
                            $modifier->setOption($newOption);
                            $modifier->runUpdate($recalculate = true);
                        }

                        return ShoppingCart::singleton()->setMessageAndReturn(_t('PickUpOrDeliveryModifier.UPDATED', 'Delivery option updated'), 'good');
                    }
                }
            }
        }

        return ShoppingCart::singleton()->setMessageAndReturn(_t('PickUpOrDeliveryModifier.UPDATED', 'Delivery option could NOT be updated'), 'bad');
    }
}
