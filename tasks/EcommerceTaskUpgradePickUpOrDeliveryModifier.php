<?php

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
use Sunnysideup\EcommerceDelivery\Modifiers\PickUpOrDeliveryModifier;

class EcommerceTaskUpgradePickUpOrDeliveryModifier extends BuildTask
{
    protected $title = 'Upgrade PickUpOrDeliveryModifier';

    protected $description = 'Fix the option field';

    private static $options_old_to_new = [];

    public function run($request)
    {
        $db = DB::get_conn();
        if ($db instanceof PostgreSQLDatabase) {
            $exist = DB::query("SELECT column_name FROM information_schema.columns WHERE table_name ='PickUpOrDeliveryModifier' AND column_name = 'PickupOrDeliveryType'")->numRecords();
        } else {
            // default is MySQL - broken for others, each database conn type supported must be checked for!
            $exist = DB::query("SHOW COLUMNS FROM \"PickUpOrDeliveryModifier\" LIKE 'PickupOrDeliveryType'")->numRecords();
        }
        if ($exist > 0) {
            $defaultOption = PickUpOrDeliveryModifierOptions::get()->filter(['IsDefault' => 1])->First();
            $modifiers = PickUpOrDeliveryModifier::get()->filter(['OptionID' => 0]);
            if ($modifiers->count()) {
                foreach ($modifiers as $modifier) {
                    if (! isset($modifier->OptionID) || ! $modifier->OptionID) {
                        if (! isset(self::$options_old_to_new[$modifier->Code])) {
                            $option = PickUpOrDeliveryModifierOptions::get()->filter(['Code' => $modifier->Code])->First();
                            if (! $option) {
                                $option = $defaultOption;
                            }
                            self::$options_old_to_new[$modifier->Code] = $option->ID;
                        }
                        $myOption = self::$options_old_to_new[$modifier->Code];
                        // USING QUERY TO UPDATE
                        DB::query('UPDATE "PickUpOrDeliveryModifier" SET "OptionID" = ' . $myOption . ' WHERE "PickUpOrDeliveryModifier"."ID" = ' . $modifier->ID);
                        DB::alteration_message('Updated modifier #' . $modifier->ID . ' from code to option ID ' . $myOption, 'edited');
                    }
                }
            }
        }
        DB::alteration_message('<hr />COMPLETED<hr />', 'created');
    }
}
