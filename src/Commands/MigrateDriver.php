<?php

namespace Arronrees\ContentGovernance\Commands;

use Arronrees\ContentGovernance\Drivers\Drivers;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;

class MigrateDriver extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:governance:migrate-driver
        {from : The driver to copy from (file or eloquent)}
        {to : The driver to copy to (file or eloquent)}';

    protected $description = 'Copy governance records and audit history from one storage driver to another';

    public function handle(Drivers $drivers): int
    {
        $from = $this->argument('from');
        $to = $this->argument('to');

        Drivers::ensureValid($from);
        Drivers::ensureValid($to);

        if ($from === $to) {
            $this->components->error('The source and target drivers are the same.');

            return self::FAILURE;
        }

        if ($to === Drivers::ELOQUENT) {
            $this->call('migrate', [
                '--path' => realpath(__DIR__.'/../../database/migrations'),
                '--realpath' => true,
                '--force' => true,
            ]);
        }

        $sourceRecords = $drivers->records($from);
        $targetRecords = $drivers->records($to);
        $sourceAudit = $drivers->audit($from);
        $targetAudit = $drivers->audit($to);

        if ($targetRecords->query()->count() > 0 || $targetAudit->all()->isNotEmpty()) {
            $this->components->error("The {$to} driver already holds governance data. Empty it first; nothing was copied.");

            return self::FAILURE;
        }

        $records = 0;
        foreach ($sourceRecords->query()->get() as $record) {
            $targetRecords->import($record);
            $records++;
        }

        $events = 0;
        foreach ($sourceAudit->all() as $event) {
            $targetAudit->append($event);
            $events++;
        }

        $this->components->info("Copied {$records} records and {$events} audit events from {$from} to {$to}.");
        $this->components->warn("Set GOVERNANCE_DRIVER={$to} to start using it. The {$from} data was left in place.");

        return self::SUCCESS;
    }
}
