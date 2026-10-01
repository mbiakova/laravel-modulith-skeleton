<?php

declare(strict_types=1);

namespace Apps\Analytics\Http\Controllers;

use Apps\Analytics\Http\Requests\ListSignupsRequest;
use Apps\Analytics\Http\Resources\SignupResource;
use Apps\Analytics\Models\Signup;
use Foundation\Iam\Contracts\IamService;
use Illuminate\Http\JsonResponse;
use Shared\Http\Controller;

final class SignupController extends Controller
{
    public function index(ListSignupsRequest $request): JsonResponse
    {
        $signups = Signup::query()->with('user')
            ->when($request->validated('user_id'), fn ($query, mixed $id) => $query->where('user_id', $id))
            ->latest('id')->get();

        return $this->success(SignupResource::collection($signups));
    }

    /** Asks iam directly: an RPC call when iam runs in another process, a plain call otherwise. */
    public function user(int $id, IamService $iam): JsonResponse
    {
        return $this->success($iam->findUser($id) ?? abort(404));
    }
}
