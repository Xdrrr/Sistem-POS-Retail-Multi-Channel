<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authentication.api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('api_client_id');
            $table->string('device_id');
            $table->string('device_type', 100);
            $table->text('fcm_token')->nullable();
            $table->ipAddress('ip_address');
            $table->string('access_token_hash', 64)->unique();
            $table->string('refresh_token_hash', 64)->unique();
            $table->timestamp('access_expires_at');
            $table->timestamp('refresh_expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table
                ->foreign('api_client_id')
                ->references('id')
                ->on('authentication.api_clients')
                ->cascadeOnDelete();
            $table->index(['device_id', 'device_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authentication.api_tokens');
    }
};
