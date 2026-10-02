<?php

declare(strict_types=1);

namespace Apps\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The figures of one hour, computed from the signups by analytics:compute: every reading folds these rows.
 *
 * @property Carbon $bucket
 * @property int $signups_count
 * @property int $users_total
 */
final class Dataset extends Model
{
    protected $table = 'analytics_datasets';

    public $timestamps = false;

    protected $fillable = ['bucket', 'signups_count', 'users_total'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'bucket' => 'datetime',
            'signups_count' => 'integer',
            'users_total' => 'integer',
        ];
    }
}
