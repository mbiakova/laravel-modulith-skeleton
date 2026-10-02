<?php

declare(strict_types=1);

namespace Apps\Analytics\Enums;

/** How the buckets of a period fold into one figure: a flow adds up, a state is the one of the last bucket. */
enum MeasureNature
{
    case Flow;
    case State;
}
