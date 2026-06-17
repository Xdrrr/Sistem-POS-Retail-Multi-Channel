<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product.inventories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->uuid('product_guid');
            $table->string('id_cabang', 50)->default('PUSAT');
            $table->string('unit', 20)->default('pcs');
            $table->decimal('current_stock', 15, 2)->default(0);
            $table->decimal('minimum_stock', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table
                ->foreign('product_guid')
                ->references('guid')
                ->on('product.products')
                ->cascadeOnDelete();

            $table->unique(['product_guid', 'id_cabang']);
            $table->index('id_cabang');
            $table->index('current_stock');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product.inventories');
    }
};
