<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product.inventories', function (Blueprint $table): void {
            $table->dropUnique(['product_guid', 'id_cabang']);
        });

        DB::statement('ALTER TABLE product.inventories RENAME COLUMN id_cabang TO guid_cabang');

        DB::statement('ALTER TABLE product.inventories ALTER COLUMN guid_cabang DROP DEFAULT');

        DB::statement("ALTER TABLE product.inventories ALTER COLUMN guid_cabang TYPE UUID USING guid_cabang::uuid");

        DB::statement("ALTER TABLE product.inventories ALTER COLUMN guid_cabang SET NOT NULL");

        Schema::table('product.inventories', function (Blueprint $table): void {
            $table->foreign('guid_cabang')->references('guid')->on('authentication.cabang');
            $table->index('guid_cabang');
            $table->unique(['product_guid', 'guid_cabang']);
        });

        DB::statement('ALTER TABLE product.inventory_history RENAME COLUMN id_cabang TO guid_cabang');

        DB::statement('ALTER TABLE product.inventory_history ALTER COLUMN guid_cabang DROP DEFAULT');

        DB::statement("ALTER TABLE product.inventory_history ALTER COLUMN guid_cabang TYPE UUID USING guid_cabang::uuid");

        Schema::table('product.inventory_history', function (Blueprint $table): void {
            $table->foreign('guid_cabang')->references('guid')->on('authentication.cabang');
            $table->index('guid_cabang');
        });
    }

    public function down(): void
    {
        Schema::table('product.inventories', function (Blueprint $table): void {
            $table->dropForeign(['guid_cabang']);
            $table->dropIndex(['guid_cabang']);
            $table->dropUnique(['product_guid', 'guid_cabang']);
        });

        DB::statement("ALTER TABLE product.inventories ALTER COLUMN guid_cabang TYPE VARCHAR(50)");

        DB::statement('ALTER TABLE product.inventories RENAME COLUMN guid_cabang TO id_cabang');

        DB::statement("ALTER TABLE product.inventories ALTER COLUMN id_cabang SET DEFAULT 'PUSAT'");

        Schema::table('product.inventories', function (Blueprint $table): void {
            $table->unique(['product_guid', 'id_cabang']);
        });

        Schema::table('product.inventory_history', function (Blueprint $table): void {
            $table->dropForeign(['guid_cabang']);
            $table->dropIndex(['guid_cabang']);
        });

        DB::statement("ALTER TABLE product.inventory_history ALTER COLUMN guid_cabang TYPE VARCHAR(50)");

        DB::statement('ALTER TABLE product.inventory_history RENAME COLUMN guid_cabang TO id_cabang');

        DB::statement("ALTER TABLE product.inventory_history ALTER COLUMN id_cabang SET DEFAULT 'PUSAT'");
    }
};
