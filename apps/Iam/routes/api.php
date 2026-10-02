<?php

declare(strict_types=1);

use Apps\Iam\Http\Controllers\TokenController;
use Apps\Iam\Http\Controllers\UserController;
use Foundation\Common\Auth\Authenticate;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:6,1')->group(function (): void {
        Route::post('users', [UserController::class, 'store']);
        Route::post('tokens', [TokenController::class, 'store']);
    });

    Route::get('me', [UserController::class, 'me'])->middleware(Authenticate::class);
});
