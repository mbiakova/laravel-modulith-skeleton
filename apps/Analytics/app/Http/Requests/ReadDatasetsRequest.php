<?php

declare(strict_types=1);

namespace Apps\Analytics\Http\Requests;

use Apps\Analytics\Enums\DatasetGroup;
use Apps\Analytics\Enums\DatasetMeasure;
use Foundation\Common\Http\ApiRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

final class ReadDatasetsRequest extends ApiRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'group' => ['sometimes', Rule::enum(DatasetGroup::class)],
            'measures' => ['sometimes', 'array'],
            'measures.*' => [Rule::enum(DatasetMeasure::class)],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after:from'],
        ];
    }

    public function group(): DatasetGroup
    {
        return DatasetGroup::from($this->validated('group', DatasetGroup::Day->value));
    }

    /** @return list<DatasetMeasure> */
    public function measures(): array
    {
        $measures = $this->validated('measures');

        return is_array($measures) ? array_values(array_map(DatasetMeasure::from(...), $measures)) : DatasetMeasure::cases();
    }

    public function from(): ?Carbon
    {
        return $this->filled('from') ? Carbon::parse($this->validated('from')) : null;
    }

    public function to(): ?Carbon
    {
        return $this->filled('to') ? Carbon::parse($this->validated('to')) : null;
    }
}
