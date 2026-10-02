<?php

declare(strict_types=1);

namespace Apps\Analytics\Enums;

use Illuminate\Support\Carbon;

/** What the hourly rows of a reading are folded onto; `none` folds the whole period into one figure. */
enum DatasetGroup: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case None = 'none';

    public function bucket(Carbon $hour): ?string
    {
        return match ($this) {
            self::Hour => $hour->format('Y-m-d H:00'),
            self::Day => $hour->format('Y-m-d'),
            self::Week => $hour->copy()->startOfWeek()->format('Y-m-d'),
            self::Month => $hour->format('Y-m'),
            self::None => null,
        };
    }
}
