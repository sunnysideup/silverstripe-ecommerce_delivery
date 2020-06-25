2020-06-25 03:30

# running php upgrade upgrade see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/upgrades/ecommerce_delivery
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code upgrade /var/www/upgrades/ecommerce_delivery/ecommerce_delivery  --root-dir=/var/www/upgrades/ecommerce_delivery --write -vvv
Writing changes for 12 files
Running upgrades on "/var/www/upgrades/ecommerce_delivery/ecommerce_delivery"
[2020-06-25 15:30:17] Applying RenameClasses to PackagingBox.php...
[2020-06-25 15:30:18] Applying ClassToTraitRule to PackagingBox.php...
[2020-06-25 15:30:18] Applying RenameClasses to _config.php...
[2020-06-25 15:30:18] Applying ClassToTraitRule to _config.php...
[2020-06-25 15:30:18] Applying RenameClasses to EcommerceTaskUpgradePickUpOrDeliveryModifier.php...
[2020-06-25 15:30:18] Applying ClassToTraitRule to EcommerceTaskUpgradePickUpOrDeliveryModifier.php...
[2020-06-25 15:30:18] Applying RenameClasses to EcommerceDeliveryTest.php...
[2020-06-25 15:30:18] Applying ClassToTraitRule to EcommerceDeliveryTest.php...
[2020-06-25 15:30:18] Applying RenameClasses to CountryRegionDeliveryModifier.php...
[2020-06-25 15:30:18] Applying ClassToTraitRule to CountryRegionDeliveryModifier.php...
[2020-06-25 15:30:18] Applying RenameClasses to PickUpOrDeliveryModifier.php...
[2020-06-25 15:30:18] Applying ClassToTraitRule to PickUpOrDeliveryModifier.php...
[2020-06-25 15:30:18] Applying RenameClasses to PickUpOrDeliveryModifierOptionsRegion.php...
[2020-06-25 15:30:18] Applying ClassToTraitRule to PickUpOrDeliveryModifierOptionsRegion.php...
[2020-06-25 15:30:18] Applying RenameClasses to PickUpOrDeliveryModifierOptions_WeightBracket.php...
[2020-06-25 15:30:18] Applying ClassToTraitRule to PickUpOrDeliveryModifierOptions_WeightBracket.php...
[2020-06-25 15:30:18] Applying RenameClasses to PickUpOrDeliveryModifierOptionsCountry.php...
[2020-06-25 15:30:18] Applying ClassToTraitRule to PickUpOrDeliveryModifierOptionsCountry.php...
[2020-06-25 15:30:18] Applying RenameClasses to PickUpOrDeliveryModifierOptions.php...
[2020-06-25 15:30:18] Applying ClassToTraitRule to PickUpOrDeliveryModifierOptions.php...
[2020-06-25 15:30:18] Applying RenameClasses to PickUpOrDeliveryModifierOptions_SubTotalBracket.php...
[2020-06-25 15:30:18] Applying ClassToTraitRule to PickUpOrDeliveryModifierOptions_SubTotalBracket.php...
[2020-06-25 15:30:18] Applying RenameClasses to PickUpOrDeliveryModifier_Form.php...
[2020-06-25 15:30:18] Applying ClassToTraitRule to PickUpOrDeliveryModifier_Form.php...
[2020-06-25 15:30:18] Applying UpdateConfigClasses to config.yml...
[2020-06-25 15:30:18] Applying UpdateConfigClasses to routes.yml...
modified:	tasks/EcommerceTaskUpgradePickUpOrDeliveryModifier.php
@@ -1,4 +1,9 @@
 <?php
+
+use SilverStripe\ORM\DB;
+use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
+use Sunnysideup\EcommerceDelivery\Modifiers\PickUpOrDeliveryModifier;
+use SilverStripe\Dev\BuildTask;

 class EcommerceTaskUpgradePickUpOrDeliveryModifier extends BuildTask
 {

modified:	tests/EcommerceDeliveryTest.php
@@ -1,4 +1,6 @@
 <?php
+
+use SilverStripe\Dev\SapphireTest;

 class EcommerceDeliveryTest extends SapphireTest
 {

modified:	src/Modifiers/CountryRegionDeliveryModifier.php
@@ -2,8 +2,11 @@

 namespace Sunnysideup\EcommerceDelivery\Modifiers;

-use DropdownField;
-use EcommerceCountry;
+
+
+use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
+use SilverStripe\Forms\DropdownField;
+


 /**

modified:	src/Modifiers/PickUpOrDeliveryModifier.php
@@ -2,23 +2,41 @@

 namespace Sunnysideup\EcommerceDelivery\Modifiers;

-use OrderModifier;
-use ReadonlyField;
-use Controller;
-use Validator;
-use Requirements;
-use PickUpOrDeliveryModifierOptions;
-use FieldList;
-use OptionsetField;
-use FormAction;
-use PickUpOrDeliveryModifier_Form;
-use EcommerceDBConfig;
-use EcommerceCountry;
-use EcommerceRegion;
-use ArrayList;
+
+
+
+
+
+
+
+
+
+
+
+
+
+
 use convert;
-use Config;
-use DB;
+
+
+use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
+use SilverStripe\Forms\ReadonlyField;
+use SilverStripe\Control\Controller;
+use SilverStripe\Forms\Validator;
+use SilverStripe\View\Requirements;
+use SilverStripe\Forms\FieldList;
+use SilverStripe\Forms\OptionsetField;
+use SilverStripe\Forms\FormAction;
+use Sunnysideup\EcommerceDelivery\Modifiers\PickUpOrDeliveryModifier;
+use Sunnysideup\EcommerceDelivery\Forms\PickUpOrDeliveryModifier_Form;
+use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
+use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
+use Sunnysideup\Ecommerce\Model\Address\EcommerceRegion;
+use SilverStripe\ORM\ArrayList;
+use SilverStripe\Core\Config\Config;
+use SilverStripe\ORM\DB;
+use Sunnysideup\Ecommerce\Model\OrderModifier;
+


 /**
@@ -61,7 +79,7 @@
     ];

     private static $has_one = array(
-        "Option" => "PickUpOrDeliveryModifierOptions"
+        "Option" => PickUpOrDeliveryModifierOptions::class
     );

     private static $singular_name = "Pickup / Delivery Charge";
@@ -268,7 +286,7 @@
         $actions = new FieldList(
             new FormAction('processOrderModifier', 'Update Pickup / Delivery Option')
         );
-        return new PickUpOrDeliveryModifier_Form($optionalController, 'PickUpOrDeliveryModifier', $fields, $actions, $optionalValidator);
+        return new PickUpOrDeliveryModifier_Form($optionalController, PickUpOrDeliveryModifier::class, $fields, $actions, $optionalValidator);
     }

     // ######################################## *** template functions (e.g. ShowInTable, TableTitle, etc...) ... USES DB VALUES
@@ -661,7 +679,7 @@
         if (self::$_total_weight === null) {
             self::$_total_weight = 0;
             if ($this->useWeight()) {
-                if ($fieldName = Config::inst()->get('PickUpOrDeliveryModifier', 'weight_field')) {
+                if ($fieldName = Config::inst()->get(PickUpOrDeliveryModifier::class, 'weight_field')) {
                     $items = $this->Order()->Items();
                     //get index numbers for bonus products - this can only be done now once they have actually been added
                     if ($items && $items->count()) {

modified:	src/Model/PickUpOrDeliveryModifierOptionsRegion.php
@@ -2,7 +2,10 @@

 namespace Sunnysideup\EcommerceDelivery\Model;

-use DataExtension;
+
+use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
+use SilverStripe\ORM\DataExtension;
+


 /**
@@ -13,7 +16,7 @@
 class PickUpOrDeliveryModifierOptionsRegion extends DataExtension
 {
     private static $belongs_many_many = array(
-        "AvailableInRegions" => "PickUpOrDeliveryModifierOptions"
+        "AvailableInRegions" => PickUpOrDeliveryModifierOptions::class
     );
 }


modified:	src/Model/PickUpOrDeliveryModifierOptions_WeightBracket.php
@@ -2,9 +2,15 @@

 namespace Sunnysideup\EcommerceDelivery\Model;

-use DataObject;
-use Permission;
-use Config;
+
+
+
+use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
+use SilverStripe\Core\Config\Config;
+use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
+use SilverStripe\Security\Permission;
+use SilverStripe\ORM\DataObject;
+



@@ -39,7 +45,7 @@
     );

     private static $belongs_many_many = array(
-        "PickUpOrDeliveryModifierOptions" => "PickUpOrDeliveryModifierOptions"
+        "PickUpOrDeliveryModifierOptions" => PickUpOrDeliveryModifierOptions::class
     );

     private static $indexes = array(
@@ -89,7 +95,7 @@
      */
     public function canCreate($member = null, $context = [])
     {
-        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
+        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
             return true;
         }
         return parent::canCreate($member);
@@ -112,7 +118,7 @@
      */
     public function canEdit($member = null, $context = [])
     {
-        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
+        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
             return true;
         }
         return parent::canEdit($member);
@@ -125,7 +131,7 @@
      */
     public function canDelete($member = null, $context = [])
     {
-        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
+        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
             return true;
         }
         return parent::canDelete($member);

modified:	src/Model/PickUpOrDeliveryModifierOptionsCountry.php
@@ -2,10 +2,16 @@

 namespace Sunnysideup\EcommerceDelivery\Model;

-use DataExtension;
-use FieldList;
-use GridField;
-use GridFieldConfig_RelationEditor;
+
+
+
+
+use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
+use SilverStripe\Forms\FieldList;
+use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
+use SilverStripe\Forms\GridField\GridField;
+use SilverStripe\ORM\DataExtension;
+


 /**
@@ -16,10 +22,10 @@
 class PickUpOrDeliveryModifierOptionsCountry extends DataExtension
 {
     private static $belongs_many_many = array(
-        "AvailableInCountries" => "PickUpOrDeliveryModifierOptions",
+        "AvailableInCountries" => PickUpOrDeliveryModifierOptions::class,
     );
     private static $many_many = array(
-        "ExcludeFromCountries" => "PickUpOrDeliveryModifierOptions"
+        "ExcludeFromCountries" => PickUpOrDeliveryModifierOptions::class
     );

     /**

modified:	src/Model/PickUpOrDeliveryModifierOptions.php
@@ -2,30 +2,63 @@

 namespace Sunnysideup\EcommerceDelivery\Model;

-use DataObject;
-use Permission;
-use Config;
-use LiteralField;
-use OptionalTreeDropdownField;
-use HeaderField;
-use GridField;
-use GridFieldBasicPageRelationConfig;
-use EcommerceDBConfig;
-use MultiSelectField;
-use GridFieldConfig;
-use GridFieldButtonRow;
-use GridFieldAddExistingAutocompleter;
-use GridFieldToolbarHeader;
-use GridFieldSortableHeader;
-use GridFieldFilterHeader;
-use GridFieldDataColumns;
-use GridFieldEditButton;
-use GridFieldDeleteAction;
-use GridFieldPageCount;
-use GridFieldPaginator;
-use GridFieldDetailForm;
-use HiddenField;
-use DB;
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+use SilverStripe\CMS\Model\SiteTree;
+use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
+use Sunnysideup\Ecommerce\Model\Address\EcommerceRegion;
+use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions_WeightBracket;
+use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions_SubTotalBracket;
+use Sunnysideup\Ecommerce\Pages\Product;
+use SilverStripe\Core\Config\Config;
+use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
+use SilverStripe\Security\Permission;
+use Sunnysideup\DataobjectSorter\DataObjectSorterController;
+use SilverStripe\Forms\LiteralField;
+use Sunnysideup\Ecommerce\Forms\Fields\OptionalTreeDropdownField;
+use SilverStripe\Forms\HeaderField;
+use Sunnysideup\Ecommerce\Forms\Gridfield\Configs\GridFieldBasicPageRelationConfig;
+use SilverStripe\Forms\GridField\GridField;
+use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
+use SilverStripe\Forms\MultiSelectField;
+use SilverStripe\Forms\GridField\GridFieldConfig;
+use SilverStripe\Forms\GridField\GridFieldButtonRow;
+use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
+use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
+use SilverStripe\Forms\GridField\GridFieldSortableHeader;
+use SilverStripe\Forms\GridField\GridFieldFilterHeader;
+use SilverStripe\Forms\GridField\GridFieldDataColumns;
+use SilverStripe\Forms\GridField\GridFieldEditButton;
+use SilverStripe\Forms\GridField\GridFieldDeleteAction;
+use SilverStripe\Forms\GridField\GridFieldPageCount;
+use SilverStripe\Forms\GridField\GridFieldPaginator;
+use SilverStripe\Forms\GridField\GridFieldDetailForm;
+use SilverStripe\Forms\HiddenField;
+use SilverStripe\ORM\DB;
+use SilverStripe\ORM\DataObject;
+


 /**
@@ -64,19 +97,19 @@
     );

     private static $has_one = array(
-        "ExplanationPage" => "SiteTree"
+        "ExplanationPage" => SiteTree::class
     );

     private static $many_many = array(
-        "AvailableInCountries" => "EcommerceCountry",
-        "AvailableInRegions" => "EcommerceRegion",
-        "WeightBrackets" => "PickUpOrDeliveryModifierOptions_WeightBracket",
-        "SubtotalBrackets" => "PickUpOrDeliveryModifierOptions_SubTotalBracket",
-        "ExcludedProducts" => 'Product'
+        "AvailableInCountries" => EcommerceCountry::class,
+        "AvailableInRegions" => EcommerceRegion::class,
+        "WeightBrackets" => PickUpOrDeliveryModifierOptions_WeightBracket::class,
+        "SubtotalBrackets" => PickUpOrDeliveryModifierOptions_SubTotalBracket::class,
+        "ExcludedProducts" => Product::class
     );

     private static $belongs_many_many = array(
-        "ExcludeFromCountries" => "EcommerceCountry",
+        "ExcludeFromCountries" => EcommerceCountry::class,
     );

     private static $indexes = array(
@@ -220,7 +253,7 @@
      */
     public function canCreate($member = null, $context = [])
     {
-        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
+        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
             return true;
         }
         return parent::canCreate($member);
@@ -233,7 +266,7 @@
      */
     public function canView($member = null, $context = [])
     {
-        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
+        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
             return true;
         }
         return parent::canCreate($member);
@@ -246,7 +279,7 @@
      */
     public function canEdit($member = null, $context = [])
     {
-        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
+        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
             return true;
         }
         return parent::canEdit($member);
@@ -259,7 +292,7 @@
      */
     public function canDelete($member = null, $context = [])
     {
-        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
+        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
             return true;
         }
         return parent::canDelete($member);
@@ -271,22 +304,22 @@
     public function getCMSFields()
     {
         $fields = parent::getCMSFields();
-        $availableInCountriesField = $this->createGridField("EcommerceCountry", "AvailableInCountries", "Available in");
+        $availableInCountriesField = $this->createGridField(EcommerceCountry::class, "AvailableInCountries", "Available in");
         if ($availableInCountriesField) {
             $fields->replaceField("AvailableInCountries", $availableInCountriesField);
         }
-        $excludeFromCountriesField = $this->createGridField("EcommerceCountry", "ExcludeFromCountries", "Excluded from");
+        $excludeFromCountriesField = $this->createGridField(EcommerceCountry::class, "ExcludeFromCountries", "Excluded from");
         if ($excludeFromCountriesField) {
             $fields->replaceField("ExcludeFromCountries", $excludeFromCountriesField);
         }
-        $regionField = $this->createGridField("EcommerceRegion", "AvailableInRegions", "Regions");
+        $regionField = $this->createGridField(EcommerceRegion::class, "AvailableInRegions", "Regions");
         if ($regionField) {
             $fields->replaceField("AvailableInRegions", $regionField);
         }
-        if (class_exists("DataObjectSorterController") && $this->hasExtension("DataObjectSorterController")) {
+        if (class_exists(DataObjectSorterController::class) && $this->hasExtension(DataObjectSorterController::class)) {
             $fields->addFieldToTab("Root.Sort", new LiteralField("InvitationToSort", $this->dataObjectSorterPopupLink()));
         }
-        $fields->replaceField("ExplanationPageID", new OptionalTreeDropdownField($name = "ExplanationPageID", $title = "Page", "SiteTree"));
+        $fields->replaceField("ExplanationPageID", new OptionalTreeDropdownField($name = "ExplanationPageID", $title = "Page", SiteTree::class));

         //add headings
         $fields->addFieldToTab(
@@ -346,12 +379,12 @@
         return $fields;
     }

-    private function createGridField($dataObjectName = "EcommerceCountry", $fieldName = "AvailableInCountries", $title)
+    private function createGridField($dataObjectName = EcommerceCountry::class, $fieldName = "AvailableInCountries", $title)
     {
         $field = null;
         $dos = $dataObjectName::get();
         if ($dos->count()) {
-            if (class_exists("MultiSelectField")) {
+            if (class_exists(MultiSelectField::class)) {
                 $array = $dos->map('ID', 'Title')->toArray();
                 //$name, $title = "", $source = array(), $value = "", $form = null
                 $field = new MultiSelectField(

Warnings for src/Model/PickUpOrDeliveryModifierOptions.php:
 - src/Model/PickUpOrDeliveryModifierOptions.php:352 PhpParser\Node\Expr\Variable
 - WARNING: New class instantiated by a dynamic value on line 352

modified:	src/Model/PickUpOrDeliveryModifierOptions_SubTotalBracket.php
@@ -2,10 +2,17 @@

 namespace Sunnysideup\EcommerceDelivery\Model;

-use DataObject;
-use Permission;
-use Config;
-use ReadonlyField;
+
+
+
+
+use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
+use SilverStripe\Core\Config\Config;
+use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
+use SilverStripe\Security\Permission;
+use SilverStripe\Forms\ReadonlyField;
+use SilverStripe\ORM\DataObject;
+



@@ -40,7 +47,7 @@
     );

     private static $belongs_many_many = array(
-        "PickUpOrDeliveryModifierOptions" => "PickUpOrDeliveryModifierOptions"
+        "PickUpOrDeliveryModifierOptions" => PickUpOrDeliveryModifierOptions::class
     );

     private static $indexes = array(
@@ -90,7 +97,7 @@
      */
     public function canCreate($member = null, $context = [])
     {
-        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
+        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
             return true;
         }
         return parent::canCreate($member);
@@ -113,7 +120,7 @@
      */
     public function canEdit($member = null, $context = [])
     {
-        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
+        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
             return true;
         }
         return parent::canEdit($member);
@@ -126,7 +133,7 @@
      */
     public function canDelete($member = null, $context = [])
     {
-        if (Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {
+        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, "admin_permission_code"))) {
             return true;
         }
         return parent::canDelete($member);

modified:	src/Forms/PickUpOrDeliveryModifier_Form.php
@@ -2,9 +2,14 @@

 namespace Sunnysideup\EcommerceDelivery\Forms;

-use OrderModifierForm;
-use PickUpOrDeliveryModifierOptions;
-use ShoppingCart;
+
+
+
+use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
+use Sunnysideup\Ecommerce\Api\ShoppingCart;
+use Sunnysideup\EcommerceDelivery\Modifiers\PickUpOrDeliveryModifier;
+use Sunnysideup\Ecommerce\Forms\OrderModifierForm;
+



@@ -18,7 +23,7 @@
             if ($newOptionObj) {
                 $order = ShoppingCart::current_order();
                 if ($order) {
-                    if ($modifiers = $order->Modifiers("PickUpOrDeliveryModifier")) {
+                    if ($modifiers = $order->Modifiers(PickUpOrDeliveryModifier::class)) {
                         foreach ($modifiers as $modifier) {
                             $modifier->setOption($newOption);
                             $modifier->runUpdate();

modified:	_config/config.yml
@@ -3,25 +3,20 @@
 Before: 'app/*'
 After: ['#coreconfig', '#cmsextensions', '#ecommerce']
 ---
-
-StoreAdmin:
+Sunnysideup\Ecommerce\Cms\StoreAdmin:
   managed_models:
-    - PickUpOrDeliveryModifierOptions
-
-OrderModifierFormController:
+    - Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions
+Sunnysideup\Ecommerce\Control\OrderModifierFormController:
   allowed_actions:
-    - PickUpOrDeliveryModifier
-
-EcommerceCountry:
+    - Sunnysideup\EcommerceDelivery\Modifiers\PickUpOrDeliveryModifier
+Sunnysideup\Ecommerce\Model\Address\EcommerceCountry:
   extensions:
-    - PickUpOrDeliveryModifierOptionsCountry
-
+    - Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptionsCountry
 ---
 Only:
   classexists: 'DataObjectSorterDOD'
 ---
+Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions:
+  extensions:
+    - Sunnysideup\DataobjectSorter\DataObjectSorterDOD

-PickUpOrDeliveryModifierOptions:
-  extensions:
-    - DataObjectSorterDOD
-

modified:	_config/routes.yml
@@ -4,5 +4,5 @@
 ---
 SilverStripe\Control\Director:
   rules:
-    'ecommercedeliverysetoption//$Action/$ID/$OtherID/$Version' : 'OrderModifierFormController'
+    ecommercedeliverysetoption//$Action/$ID/$OtherID/$Version: Sunnysideup\Ecommerce\Control\OrderModifierFormController


Writing changes for 12 files
✔✔✔