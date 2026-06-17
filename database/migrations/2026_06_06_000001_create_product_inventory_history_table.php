<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product.inventory_history', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->uuid('inventory_id');
            $table->uuid('product_guid');
            $table->string('id_cabang', 50);
            $table->string('type');
            $table->decimal('qty', 15, 2);
            $table->decimal('stock_before', 15, 2);
            $table->decimal('stock_after', 15, 2);
            $table->string('reference_type', 50)->nullable();
            $table->uuid('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table
                ->foreign('inventory_id')
                ->references('guid')
                ->on('product.inventories')
                ->cascadeOnDelete();

            $table
                ->foreign('product_guid')
                ->references('guid')
                ->on('product.products');

            $table
                ->foreign('created_by')
                ->references('guid')
                ->on('authentication.users');

            $table->index('inventory_id');
            $table->index('product_guid');
            $table->index('id_cabang');
            $table->index('reference_type');
            $table->index('reference_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product.inventory_history');
    }
};
