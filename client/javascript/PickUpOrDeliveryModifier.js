/**
 * make sure it can import EcomCart
 * see init
 */

if(
    (document.getElementById("PickUpOrDeliveryModifierForm_PickUpOrDeliveryModifier") !== null && typeof document.getElementById("PickUpOrDeliveryModifierForm_PickUpOrDeliveryModifier") !== "undefined")
) {

    (function($){
        $(document).ready(
            function() {
                PickUpOrDeliveryModifier.init();
            }
        );
    })(jQuery);

    var PickUpOrDeliveryModifier = {

        formID: "PickUpOrDeliveryModifierForm_PickUpOrDeliveryModifier",

        optionSetOptions: "PickupOrDeliveryType",

        loadingClass: "loading",

        actionsClass: "Actions",

        countryDropdownSelector: "select.ajaxCountryField",

        notSelectedText: "-- not selected --",

        cartMessageClass: ".cartMessage",

        availableCountries: new Array(),

        EcomCart: {},

        init: function() {
            if(typeof window.EcomCart === "undefined" ) {
                var EcomCart = require("./EcomCartWebPack");
                this.EcomCart = EcomCart.EcomCart;
            } else {
                PickUpOrDeliveryModifier.EcomCart = window.EcomCart;
            }
            if(typeof PickUpOrDeliveryModifierOptions !== "undefined") {
                this.availableCountries = PickUpOrDeliveryModifierOptions;
            }
            if(jQuery("#" + PickUpOrDeliveryModifier.formID + " input[name='"+PickUpOrDeliveryModifier.optionSetOptions+"']").length < 1) {
                jQuery("#" + PickUpOrDeliveryModifier.formID).hide();
            }
            else {
                jQuery("#" + PickUpOrDeliveryModifier.formID).show();
                var options = {
                    beforeSubmit:  PickUpOrDeliveryModifier.showRequest,  // pre-submit callback
                    success: PickUpOrDeliveryModifier.showResponse,  // post-submit callback
                    dataType: "json"
                };
                jQuery('#' + PickUpOrDeliveryModifier.formID).ajaxForm(options);
                jQuery("#" + PickUpOrDeliveryModifier.formID + " ." + PickUpOrDeliveryModifier.actionsClass).hide();
                PickUpOrDeliveryModifier.updateCountryList();
                var selectorForField = "#" + PickUpOrDeliveryModifier.formID  + " input[name='"+PickUpOrDeliveryModifier.optionSetOptions+"']";
                jQuery(selectorForField).change(
                    function() {
                        PickUpOrDeliveryModifier.updateCountryList();
                        jQuery("#" + PickUpOrDeliveryModifier.formID).submit();
                    }
                );
                //jQuery(selectorForField).change();
            }
        },

        // pre-submit callback
        showRequest: function (formData, jqForm, options) {
            jQuery("#" + PickUpOrDeliveryModifier.formID).addClass(PickUpOrDeliveryModifier.loadingClass);
            return true;
        },

        // post-submit callback
        showResponse: function (responseText, statusText)  {
            //redo quantity boxes
            //jQuery("#" + PickUpOrDeliveryModifier.updatedDivID).css("height", "auto");
            jQuery("#" + PickUpOrDeliveryModifier.formID).removeClass(PickUpOrDeliveryModifier.loadingClass);
            PickUpOrDeliveryModifier.EcomCart.setChanges(responseText);
        },

        addAvailableCountriesItem: function(index, countriesArray) {
            PickUpOrDeliveryModifier.availableCountries[index] = countriesArray;
            return this;
        },

        /**
         * alias for addAvailableCountriesItem
         */
        addItem: function(index, countriesArray) {
            this.addAvailableCountriesItem(index, countriesArray);
            return this;
        },

        updateCountryList: function() {
            var currentIndex = jQuery("#" + PickUpOrDeliveryModifier.formID + " input[name='"+PickUpOrDeliveryModifier.optionSetOptions+"']").val();
            var currentCountryValue = jQuery(PickUpOrDeliveryModifier.countryDropdownSelector).val();
            var acceptableOptions = PickUpOrDeliveryModifier.availableCountries[currentIndex];
            var hasValidValue = false;
            if(acceptableOptions ==undefined || typeof(acceptableOptions) == 'undefined' ) {
                acceptableOptions = new Array();
            }
            if(acceptableOptions.length < 1) {
                jQuery(PickUpOrDeliveryModifier.countryDropdownSelector + " option").show();
            }
            else {
                jQuery(PickUpOrDeliveryModifier.countryDropdownSelector + " option").hide();
            }
            for(var i=0; i<acceptableOptions.length;i++) {
                jQuery(PickUpOrDeliveryModifier.countryDropdownSelector + " option[value='" + acceptableOptions[i] + "']").show();
                if(currentCountryValue == acceptableOptions[i]) {
                    hasValidValue = true;
                }
            }
            if(acceptableOptions.length == 1) {
                jQuery(PickUpOrDeliveryModifier.countryDropdownSelector).val(acceptableOptions[0]);
                hasValidValue = true;
            }
            if(hasValidValue) {
                jQuery(PickUpOrDeliveryModifier.countryDropdownSelector + " option.nothingSelected").hide();
            }
            else if(acceptableOptions.length > 0) {
                PickUpOrDeliveryModifier.nothingSelected();
                jQuery(PickUpOrDeliveryModifier.countryDropdownSelector).change();
            }
        },

        nothingSelected: function() {
            if(jQuery(PickUpOrDeliveryModifier.countryDropdownSelector + " option.nothingSelected").length < 1) {
                jQuery(PickUpOrDeliveryModifier.countryDropdownSelector).prepend('<option class="nothingSelected" value="-">'+PickUpOrDeliveryModifier.notSelectedText+'</option>');
            }
            else {
                jQuery(PickUpOrDeliveryModifier.countryDropdownSelector + " option.nothingSelected").show();
            }
            jQuery(PickUpOrDeliveryModifier.countryDropdownSelector).val("-");
        }


    }
}
