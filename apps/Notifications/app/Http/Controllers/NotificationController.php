<?php

declare(strict_types=1);

namespace Apps\Notifications\Http\Controllers;

use Apps\Notifications\Http\Resources\NotificationResource;
use Apps\Notifications\Models\Notification;
use Apps\Notifications\Repositories\NotificationRepository;
use Foundation\Common\Http\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Every query is scoped to the authenticated recipient: someone else's notification is a 404. */
final class NotificationController extends Controller
{
    /** ?filter[unread]=1, ?sort=-created_at, ?paginate=20 */
    public function index(Request $request, NotificationRepository $notifications): JsonResponse
    {
        return $this->success(NotificationResource::collection($notifications->all($request->query(), $this->ownInbox(...))));
    }

    public function read(int $id): JsonResponse
    {
        $notification = Notification::query()->tap($this->ownInbox(...))->findOrFail($id);
        $notification->read_at ??= now();
        $notification->save();

        return $this->success(NotificationResource::make($notification));
    }

    /** @param Builder<Notification> $query */
    private function ownInbox(Builder $query): void
    {
        $query->addressedTo($this->principalIdOrFail())->with('recipient');
    }
}
