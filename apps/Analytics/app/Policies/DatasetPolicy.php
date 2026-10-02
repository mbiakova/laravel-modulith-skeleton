<?php

declare(strict_types=1);

namespace Apps\Analytics\Policies;

use Foundation\Analytics\Enums\AnalyticsPermission;
use Foundation\Common\Policies\Policy;

final class DatasetPolicy extends Policy
{
    public function viewAny(mixed $user): bool
    {
        return $this->allows($user, AnalyticsPermission::ReadDatasets);
    }
}
