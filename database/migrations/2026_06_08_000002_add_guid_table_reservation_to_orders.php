<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders.orders', function (Blueprint $table): void {
            $table->uuid('guid_table_reservation')->nullable()->after('table_number');

            $table
                ->foreign('guid_table_reservation')
                ->references('guid')
                ->on('orders.table_reservations');

            $table->index('guid_table_reservation');
        });
    }

    public function down(): void
    {
        Schema::table('orders.orders', function (Blueprint $table): void {
            $table->dropForeign(['guid_table_reservation']);
            $table->dropIndex(['guid_table_reservation']);
            $table->dropColumn('guid_table_reservation');
        });
    }
};
