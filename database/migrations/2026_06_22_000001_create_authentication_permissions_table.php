<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authentication.permissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->string('name', 100)->unique();
            $table->string('display_name', 200);
            $table->string('group', 50);
            $table->string('type', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authentication.permissions');
    }
};
