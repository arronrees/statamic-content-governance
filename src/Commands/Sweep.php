<?php

namespace Arronrees\ContentGovernance\Commands;

use Arronrees\ContentGovernance\Contracts\Clock;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Expiry\UnpublishExpiredEntry;
use Arronrees\ContentGovernance\Records\EntryRecords;
use Arronrees\ContentGovernance\Support\SweepHeartbeat;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Throwable;

class Sweep extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:governance:sweep';

    protected $description = 'Move governance records whose next action has arrived: update statuses and unpublish expired content';

    public function handle(
        GovernanceRecordRepository $records,
        UnpublishExpiredEntry $unpublish,
        SweepHeartbeat $heartbeat,
        EntryRecords $entries,
        Clock $clock,
    ): int {
        $now = $clock->now();
        $processed = 0;
        $failed = 0;

        foreach ($records->query()->whereActionDue($now)->get() as $record) {
            // One broken record mustn't stop the rest from being swept.
            try {
                if (! $entries->entryExists($record)) {
                    $entries->removeOrphan($record);

                    continue;
                }

                $result = $records->save($record);

                if ($result->shouldUnpublish) {
                    $unpublish($record);
                }

                $processed++;
            } catch (Throwable $e) {
                report($e);
                $failed++;
                $this->components->error("Couldn't sweep governance record [{$record->id()}]: {$e->getMessage()}");
            }
        }

        $heartbeat->beat($now);

        $this->components->info("Swept {$processed} governance records.");

        if ($failed > 0) {
            $this->components->warn("{$failed} could not be swept; they'll be retried next run.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
