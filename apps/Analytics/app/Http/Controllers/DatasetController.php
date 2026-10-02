<?php

declare(strict_types=1);

namespace Apps\Analytics\Http\Controllers;

use Apps\Analytics\Http\Requests\ReadDatasetsRequest;
use Apps\Analytics\Models\Dataset;
use Apps\Analytics\Queries\ReadDatasets;
use Foundation\Common\Http\Controller;
use Illuminate\Http\JsonResponse;

final class DatasetController extends Controller
{
    public function index(ReadDatasetsRequest $request, ReadDatasets $read): JsonResponse
    {
        $this->authorize('viewAny', Dataset::class);

        return $this->success($read->execute($request->group(), $request->measures(), $request->from(), $request->to()));
    }
}
