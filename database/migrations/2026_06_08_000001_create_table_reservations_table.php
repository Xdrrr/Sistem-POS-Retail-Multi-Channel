<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS orders');

        Schema::create('orders.table_reservations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->string('table_number', 30);
            $table->string('customer_name', 150);
            $table->string('customer_phone', 30)->nullable();
            $table->integer('guest_count')->default(1);
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->uuid('guid_cabang')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('guid_cabang')->references('guid')->on('authentication.cabang');
            $table->index('table_number');
            $table->index('reservation_date');
            $table->index('status');
            $table->index('guid_cabang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders.table_reservations');
    }
};
