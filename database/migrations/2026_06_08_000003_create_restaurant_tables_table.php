<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders.tables', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->string('table_number', 30)->unique();
            $table->integer('capacity')->default(4);
            $table->string('location', 50)->default('indoor');
            $table->string('status', 20)->default('available');
            $table->uuid('guid_cabang')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('guid_cabang')->references('guid')->on('authentication.cabang');
            $table->index('status');
            $table->index('guid_cabang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders.tables');
    }
};
