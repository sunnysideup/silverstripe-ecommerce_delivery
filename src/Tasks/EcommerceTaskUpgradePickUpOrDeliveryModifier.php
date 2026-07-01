<?php

namespace Sunnysideup\EcommerceDelivery\Tasks;

use Symfony\Component\Console\Input\InputInterface;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;
use Sunnysideup\EcommerceDelivery\Modifiers\PickUpOrDeliveryModifier;

class EcommerceTaskUpgradePickUpOrDeliveryModifier extends BuildTask
{
    protected string $title = 'Upgrade PickUpOrDeliveryModifier';

    protected static string $description = 'Fix the option field';

    private static $options_old_to_new = [];

    protected static string $commandName = 'EcommerceTaskUpgradePickUpOrDeliveryModifier';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $exist = DB::query('SHOW COLUMNS FROM "PickUpOrDeliveryModifier" LIKE \'PickupOrDeliveryType\'')->numRecords();
        if ($exist > 0) {
            $defaultOption = PickUpOrDeliveryModifierOptions::get()->filter(['IsDefault' => 1])->first();
            $modifiers = PickUpOrDeliveryModifier::get()->filter(['OptionID' => 0]);
            if ($modifiers->exists()) {
                foreach ($modifiers as $modifier) {
                    if (! (property_exists($modifier, 'OptionID') && null !== $modifier->OptionID) || ! $modifier->OptionID) {
                        if (! array_key_exists($modifier->Code, self::$options_old_to_new)) {
                            $option = PickUpOrDeliveryModifierOptions::get()->filter(['Code' => $modifier->Code])->first();
                            if (! $option) {
                                $option = $defaultOption;
                            }

                            self::$options_old_to_new[$modifier->Code] = $option->ID;
                        }

                        $myOption = self::$options_old_to_new[$modifier->Code];
                        // USING QUERY TO UPDATE
                        DB::query('UPDATE "PickUpOrDeliveryModifier" SET "OptionID" = ' . $myOption . ' WHERE "PickUpOrDeliveryModifier"."ID" = ' . $modifier->ID);
                        $output->writeln('Updated modifier #' . $modifier->ID . ' from code to option ID ' . $myOption);
                    }
                }
            }
        }

        $output->writeln('<hr />COMPLETED<hr />');
        return Command::SUCCESS;
    }
}
