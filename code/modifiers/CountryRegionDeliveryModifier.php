<?php

/**
 * to do: delete this class!
 *
 *
 *
 *
 *
 *
 */

class CountryRegionDeliveryModifier extends PickUpOrDeliveryModifier
{


    // ######################################## *** cms variables + functions (e.g. getCMSFields, $searchableFields)

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fieldLabels = $this->Config()->get("field_labels");
        $fields->replaceField(
            "CountryCode",
            new DropDownField(
                "CountryCode",
                $fieldLabels["CountryCode"],
                EcommerceCountry::get_country_dropdown()
            )
        );
        return $fields;
    }

    public function TableSubTitle()
    {
        return $this->getTableSubTitle();
    }
    public function getTableSubTitle()
    {
        return $this->RegionAndCountry;
    }
}
