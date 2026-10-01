<?php

declare(strict_types=1);

namespace Shared\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as BaseModel;

/** Free-text scope over the model's `$searchable` columns, dotted paths included. */
trait Searchable
{
    /** @var list<string> */
    protected array $searchable = [];

    /**
     * @param  Builder<BaseModel>  $q
     * @return Builder<BaseModel>
     */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        // Case-insensitive on every driver: ilike is PostgreSQL-only.
        $operator = $q->getModel()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $q->where(function (Builder $q) use ($term, $operator) {
            foreach ($this->searchable as $col) {
                if (str_contains($col, '.')) {
                    $parts = explode('.', $col);
                    $q->orWhereHas($parts[0], fn ($r) => $r->where($parts[1], $operator, "%{$term}%"));
                } else {
                    $q->orWhere($col, $operator, "%{$term}%");
                }
            }
        });
    }
}
