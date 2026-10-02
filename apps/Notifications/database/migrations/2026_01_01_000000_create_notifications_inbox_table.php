<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_inbox', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('recipient_user_id');
            $table->string('type');
            $table->json('payload');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['recipient_user_id', 'type']);
            $table->index(['recipient_user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_inbox');
    }
};
