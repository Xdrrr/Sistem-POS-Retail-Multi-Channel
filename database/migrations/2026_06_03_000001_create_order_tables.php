<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS orders CASCADE');
        DB::statement('CREATE SCHEMA IF NOT EXISTS orders');

        Schema::create('orders.orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->string('order_number', 30)->unique();
            $table->string('customer_name', 150)->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->string('table_number', 30)->nullable();
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery'])->default('dine_in');
            $table->enum('status', ['draft', 'open', 'completed', 'cancelled'])->default('open');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('orders.order_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->uuid('order_guid');
            $table->uuid('product_guid');
            $table->string('product_name', 150);
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table
                ->foreign('order_guid')
                ->references('guid')
                ->on('orders.orders')
                ->cascadeOnDelete();
            $table
                ->foreign('product_guid')
                ->references('guid')
                ->on('product.products')
                ->restrictOnDelete();
            $table->index(['order_guid', 'product_guid']);
        });

        Schema::create('orders.payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->uuid('order_guid');
            $table->string('payment_number', 30)->unique();
            $table->enum('method', ['cash', 'debit_card', 'credit_card', 'qris', 'transfer', 'e_wallet']);
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('paid');
            $table->decimal('amount', 15, 2);
            $table->timestamp('paid_at')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table
                ->foreign('order_guid')
                ->references('guid')
                ->on('orders.orders')
                ->cascadeOnDelete();
            $table->index(['order_guid', 'method', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders.payments');
        Schema::dropIfExists('orders.order_items');
        Schema::dropIfExists('orders.orders');
        DB::statement('DROP SCHEMA IF EXISTS orders');
    }
};
