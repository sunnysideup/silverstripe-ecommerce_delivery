<?php


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
                    if ($modifiers = $order->Modifiers("PickUpOrDeliveryModifier")) {
                        foreach ($modifiers as $modifier) {
                            $modifier->setOption($newOption);
                            $modifier->runUpdate();
                        }
                        return ShoppingCart::singleton()->setMessageAndReturn(_t("PickUpOrDeliveryModifier.UPDATED", "Delivery option updated"), "good");
                    }
                }
            }
        }
        return ShoppingCart::singleton()->setMessageAndReturn(_t("PickUpOrDeliveryModifier.UPDATED", "Delivery option could NOT be updated"), "bad");
    }
}
