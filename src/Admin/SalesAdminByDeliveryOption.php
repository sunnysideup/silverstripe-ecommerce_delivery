<?php

namespace Sunnysideup\EcommerceDelivery\Admin;

use Sunnysideup\Ecommerce\Cms\SalesAdmin;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
use Sunnysideup\EcommerceDelivery\Modifiers\PickUpOrDeliveryModifier;

class SalesAdminByDeliveryOption extends SalesAdmin
{
    private static $required_permission_codes = 'CMS_ACCESS_SalesAdminByDeliveryOption';
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'sales-by-delivery-option';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = '... by Delivery';

    private static $menu_priority = 3.111;
    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $managed_models = [
        Order::class,
    ];

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $fields = $form->Fields();
        if (is_subclass_of($this->modelClass, Order::class) || Order::class === $this->modelClass) {
            $brackets = $this->getBrackets();
            $arrayOfTabs = array_fill_keys(array_keys($brackets), ['IDs' => []]);
            $baseList = $this->getList();
            $optionPerOrder = $this->getOptionPerOrder($baseList);
            foreach ($baseList as $order) {
                $option = $optionPerOrder[$order->ID] ?? 0;
                foreach (array_keys($brackets) as $key) {
                    if ($option === $key) {
                        $arrayOfTabs[$key]['IDs'][$order->ID] = $order->ID;
                    }
                }
            }
            $this->buildTabs($brackets, $arrayOfTabs, $form);
        }

        return $form;
    }

    protected function getBrackets(): array
    {
        $list = PickUpOrDeliveryModifierOptions::get();
        if ($list->exists()) {
            return (array) $list->map()->toArray();
        }

        return [];
    }

    protected function getOptionPerOrder($baseList): array
    {
        if ($baseList->exists()) {
            $list = PickUpOrDeliveryModifier::get()->
                filter(['OrderID' => $baseList->columnUnique()]);
            if ($list->exists()) {
                return $list->map('OrderID', 'OptionID')->toArray();
            }
        }

        return [];
    }
}
