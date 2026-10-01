<?php

declare(strict_types=1);

use Apps\Analytics\Http\Controllers\SignupController;
use Illuminate\Support\Facades\Route;
use Shared\Auth\Authenticate;

Route::prefix('v1')->middleware(Authenticate::class)->group(function (): void {
    Route::get('signups', [SignupController::class, 'index']);
    Route::get('users/{id}', [SignupController::class, 'user']);
});
