<?php

declare(strict_types=1);

namespace Apps\Analytics\Handlers;

use Apps\Analytics\Models\Signup;
use Modulith\Contracts\Stream\Handler;

final class RecordSignup implements Handler
{
    public function handle(string $name, array $payload): void
    {
        Signup::query()->firstOrCreate(['user_id' => (int) $payload['id']]);
    }
}
