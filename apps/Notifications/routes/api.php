<?php

declare(strict_types=1);

use Apps\Notifications\Http\Controllers\NotificationController;
use Foundation\Common\Auth\Authenticate;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(Authenticate::class)->group(function (): void {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'read']);
});
