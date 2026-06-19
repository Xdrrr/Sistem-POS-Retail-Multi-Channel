<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders.table_reservations', function (Blueprint $table): void {
            $table->time('end_time')->nullable()->after('reservation_time');
            $table->string('type', 20)->default('booking')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders.table_reservations', function (Blueprint $table): void {
            $table->dropColumn(['end_time', 'type']);
        });
    }
};
