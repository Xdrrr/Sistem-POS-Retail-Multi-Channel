<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product.products', function (Blueprint $table): void {
            $table->string('sku', 50)->unique()->nullable()->after('guid');
            $table->uuid('guid_cabang')->nullable()->after('sku');
            $table
                ->foreign('guid_cabang')
                ->references('guid')
                ->on('authentication.cabang');
            $table->index('guid_cabang');
        });

        Schema::table('authentication.users', function (Blueprint $table): void {
            $table->uuid('guid_cabang')->nullable()->after('role_id');
            $table
                ->foreign('guid_cabang')
                ->references('guid')
                ->on('authentication.cabang');
            $table->index('guid_cabang');
        });

        Schema::table('orders.orders', function (Blueprint $table): void {
            $table->uuid('guid_cabang')->nullable()->after('user_id');
            $table
                ->foreign('guid_cabang')
                ->references('guid')
                ->on('authentication.cabang');
            $table->index('guid_cabang');
        });

        Schema::table('orders.shifts', function (Blueprint $table): void {
            $table->uuid('guid_cabang')->nullable()->after('user_guid');
            $table
                ->foreign('guid_cabang')
                ->references('guid')
                ->on('authentication.cabang');
            $table->index('guid_cabang');
        });

        Schema::table('report_exports', function (Blueprint $table): void {
            $table->uuid('guid_cabang')->nullable()->after('requested_by');
            $table
                ->foreign('guid_cabang')
                ->references('guid')
                ->on('authentication.cabang');
            $table->index('guid_cabang');
        });
    }

    public function down(): void
    {
        Schema::table('product.products', function (Blueprint $table): void {
            $table->dropForeign(['guid_cabang']);
            $table->dropIndex(['guid_cabang']);
            $table->dropColumn(['guid_cabang', 'sku']);
        });

        foreach (['authentication.users', 'orders.orders', 'orders.shifts', 'report_exports'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropForeign(['guid_cabang']);
                $table->dropIndex(['guid_cabang']);
                $table->dropColumn('guid_cabang');
            });
        }
    }
};
