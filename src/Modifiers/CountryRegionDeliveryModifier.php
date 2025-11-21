<?php

namespace Sunnysideup\EcommerceDelivery\Modifiers;

use SilverStripe\Forms\DropdownField;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;

/**
 * Class \Sunnysideup\EcommerceDelivery\Modifiers\CountryRegionDeliveryModifier
 *
 */
class CountryRegionDeliveryModifier extends PickUpOrDeliveryModifier
{
    // ######################################## *** cms variables + functions (e.g. getCMSFields, $searchableFields)

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fieldLabels = $this->Config()->get('field_labels');
        $fields->replaceField(
            'CountryCode',
            new DropdownField(
                'CountryCode',
                $fieldLabels['CountryCode'],
                EcommerceCountry::get_country_dropdown()
            )
        );

        return $fields;
    }


    public function getTableSubTitle(): string
    {
        if ($this->priceHasBeenFixed()) {
            return (string) $this->TableSubTitleFixed;
        }
        return (string) $this->RegionAndCountry;
    }
}
