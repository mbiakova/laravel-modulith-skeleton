<?php

declare(strict_types=1);

namespace Apps\Analytics\Queries;

use Apps\Analytics\Enums\DatasetGroup;
use Apps\Analytics\Enums\DatasetMeasure;
use Apps\Analytics\Models\Dataset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class ReadDatasets
{
    /**
     * @param  list<DatasetMeasure>  $measures
     * @return list<array<string, int|string|null>>
     */
    public function execute(DatasetGroup $group, array $measures, ?Carbon $from = null, ?Carbon $to = null): array
    {
        return Dataset::query()
            ->when($from, fn ($query, Carbon $from) => $query->where('bucket', '>=', $from))
            ->when($to, fn ($query, Carbon $to) => $query->where('bucket', '<', $to))
            ->orderBy('bucket')
            ->get()
            ->groupBy(fn (Dataset $row): string => (string) $group->bucket($row->bucket))
            ->map(fn (Collection $rows, string $bucket): array => ['bucket' => $bucket === '' ? null : $bucket, ...$this->fold($rows, $measures)])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Dataset>  $rows
     * @param  list<DatasetMeasure>  $measures
     * @return array<string, int>
     */
    private function fold(Collection $rows, array $measures): array
    {
        $figures = [];

        foreach ($measures as $measure) {
            $figures[$measure->value] = $measure->fold($rows->map(fn (Dataset $row): int => (int) $row->getAttribute($measure->value)));
        }

        return $figures;
    }
}
