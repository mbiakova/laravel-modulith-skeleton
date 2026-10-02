<?php

declare(strict_types=1);

namespace Apps\Notifications\Repositories;

use Apps\Notifications\Models\Notification;
use Foundation\Common\Database\EloquentRepository;
use Spatie\QueryBuilder\AllowedFilter;

/** @extends EloquentRepository<Notification> */
final class NotificationRepository extends EloquentRepository
{
    protected string $model = Notification::class;

    protected string $defaultSort = '-id';

    public function __construct()
    {
        parent::__construct(
            filters: [AllowedFilter::scope('unread')],
            sorts: ['id', 'created_at'],
        );
    }
}
