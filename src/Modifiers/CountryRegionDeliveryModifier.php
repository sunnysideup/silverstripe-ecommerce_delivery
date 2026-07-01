<?php

namespace Sunnysideup\EcommerceDelivery\Modifiers;

use Override;
use SilverStripe\Forms\DropdownField;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;

/**
 * Class \Sunnysideup\EcommerceDelivery\Modifiers\CountryRegionDeliveryModifier
 */
class CountryRegionDeliveryModifier extends PickUpOrDeliveryModifier
{
    private static $table_name = 'CountryRegionDeliveryModifier';

    // ######################################## *** cms variables + functions (e.g. getCMSFields, $searchableFields)

    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fieldLabels = $this->Config()->get('field_labels');
        $fields->replaceField(
            'CountryCode',
            DropdownField::create('CountryCode', $fieldLabels['CountryCode'], EcommerceCountry::get_country_dropdown())
        );

        return $fields;
    }

    #[Override]
    public function getTableSubTitle(): string
    {
        if ($this->priceHasBeenFixed()) {
            return (string) $this->TableSubTitleFixed;
        }

        return (string) $this->RegionAndCountry;
    }
}
