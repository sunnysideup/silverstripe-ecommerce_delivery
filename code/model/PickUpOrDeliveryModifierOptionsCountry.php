<?php

/**
 *@author nicolaas [at] sunnysideup.co.nz
 *
 **/

class PickUpOrDeliveryModifierOptionsCountry extends DataExtension {

    private static $belongs_many_many = array(
        "AvailableInCountries" => "PickUpOrDeliveryModifierOptions",
    );
    private static $many_many = array(
        "ExcludeFromCountries" => "PickUpOrDeliveryModifierOptions"
    );

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeFieldFromTab('Root', 'AvailableInCountries');
        $fields->removeFieldFromTab('Root', 'ExcludeFromCountries');
        $fields->addFieldsToTab(
            "Root.Delivery",
            array(
                new GridField(
                    'AvailableInCountries',
                    'Included',
                    $this->owner->AvailableInCountries(),
                    GridFieldConfig_RelationEditor::create()
                ),
                new GridField(
                    'ExcludeFromCountries',
                    'Excluded',
                    $this->owner->ExcludeFromCountries(),
                    GridFieldConfig_RelationEditor::create()
                ),
            )
        );
        return $fields;
    }

}
