<?php

declare(strict_types=1);

namespace Apps\Analytics\Console;

use Apps\Analytics\Models\Dataset;
use Apps\Analytics\Models\Signup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** A dataset row is derived: it is rebuilt from the signups, never edited. */
final class ComputeDatasets extends Command
{
    protected $signature = 'analytics:compute';

    protected $description = 'Rebuild the hourly datasets from the signups';

    public function handle(): int
    {
        $hours = Signup::query()->oldest()->get(['created_at'])
            ->countBy(fn (Signup $signup): string => $signup->created_at->copy()->startOfHour()->toDateTimeString());

        DB::transaction(function () use ($hours): void {
            Dataset::query()->delete();
            $total = 0;

            foreach ($hours as $bucket => $count) {
                $total += $count;
                Dataset::query()->create(['bucket' => $bucket, 'signups_count' => $count, 'users_total' => $total]);
            }
        });

        $this->info("{$hours->count()} hourly buckets computed.");

        return self::SUCCESS;
    }
}
