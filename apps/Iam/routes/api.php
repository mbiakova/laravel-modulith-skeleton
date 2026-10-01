<?php

declare(strict_types=1);

use Apps\Iam\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Shared\Auth\Authenticate;

Route::prefix('v1')->group(function (): void {
    Route::post('users', [UserController::class, 'store']);
    Route::get('me', [UserController::class, 'me'])->middleware(Authenticate::class);
});
