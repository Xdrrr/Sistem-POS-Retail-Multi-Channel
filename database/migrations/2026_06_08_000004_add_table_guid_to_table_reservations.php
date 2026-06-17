<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders.table_reservations', function (Blueprint $table): void {
            $table->uuid('table_guid')->nullable()->after('guid');

            $table
                ->foreign('table_guid')
                ->references('guid')
                ->on('orders.tables');

            $table->index('table_guid');
        });
    }

    public function down(): void
    {
        Schema::table('orders.table_reservations', function (Blueprint $table): void {
            $table->dropForeign(['table_guid']);
            $table->dropIndex(['table_guid']);
            $table->dropColumn('table_guid');
        });
    }
};
