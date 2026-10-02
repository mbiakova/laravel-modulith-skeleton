<?php

declare(strict_types=1);

namespace Apps\Analytics\Enums;

use Illuminate\Support\Collection;

/** A measure column of analytics_datasets. */
enum DatasetMeasure: string
{
    case SignupsCount = 'signups_count';
    case UsersTotal = 'users_total';

    public function nature(): MeasureNature
    {
        return match ($this) {
            self::SignupsCount => MeasureNature::Flow,
            self::UsersTotal => MeasureNature::State,
        };
    }

    /** @param Collection<int, int> $values the measure of each bucket, oldest first */
    public function fold(Collection $values): int
    {
        return match ($this->nature()) {
            MeasureNature::Flow => (int) $values->sum(),
            MeasureNature::State => (int) $values->last(),
        };
    }
}
