<?php

declare(strict_types=1);

namespace Foundation\Analytics\Enums;

/** The permissions analytics checks; iam:sync-permissions creates them in iam. */
enum AnalyticsPermission: string
{
    case ReadDatasets = 'analytics.datasets.read';
}
