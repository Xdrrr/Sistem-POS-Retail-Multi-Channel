<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authentication.roles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->string('name', 100)->unique();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('authentication.users', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->foreignId('role_id');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('salt');
            $table->boolean('is_active')->default(true);
            $table->string('url_image')->default('');
            $table->text('fcm_token')->default('');
            $table->timestamp('last_login')->nullable();
            $table->boolean('used_trial')->default(true);
            $table->boolean('is_verified')->default(true);
            $table->timestamps();

            $table
                ->foreign('role_id')
                ->references('id')
                ->on('authentication.roles')
                ->restrictOnDelete();
        });

        Schema::create('authentication.user_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('phone_number')->nullable();
            $table->string('email')->unique();
            $table->string('full_name');
            $table->string('gender', 30);
            $table->text('address')->nullable();
            $table->json('additional_address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->timestamps();

            $table
                ->foreign('user_id')
                ->references('id')
                ->on('authentication.users')
                ->cascadeOnDelete();
        });

        Schema::create('authentication.authentications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->foreignId('user_id');
            $table->foreignId('api_token_id')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table
                ->foreign('user_id')
                ->references('id')
                ->on('authentication.users')
                ->cascadeOnDelete();
            $table
                ->foreign('api_token_id')
                ->references('id')
                ->on('authentication.api_tokens')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authentication.authentications');
        Schema::dropIfExists('authentication.user_details');
        Schema::dropIfExists('authentication.users');
        Schema::dropIfExists('authentication.roles');
    }
};
