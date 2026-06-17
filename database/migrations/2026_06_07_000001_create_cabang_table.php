<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authentication.cabang', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->string('kode', 50)->unique();
            $table->string('nama', 100);
            $table->text('alamat')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authentication.cabang');
    }
};
