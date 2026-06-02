<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('authentication.api_clients');
        Schema::create('authentication.api_clients', function (Blueprint $table): void {
            $table->id();
            $table->string('app_name')->unique();
            $table->string('app_key_hash');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authentication.api_clients');
    }
};
