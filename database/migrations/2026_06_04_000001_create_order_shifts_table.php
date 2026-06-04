<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders.shifts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->foreignId('user_id');
            $table->uuid('user_guid');
            $table->string('shift_number', 30)->unique();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('work_hours', 8, 2)->default(0);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->nullable();
            $table->decimal('expected_balance', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();

            $table
                ->foreign('user_id')
                ->references('id')
                ->on('authentication.users')
                ->restrictOnDelete();
            $table
                ->foreign('user_guid')
                ->references('guid')
                ->on('authentication.users')
                ->restrictOnDelete();
            $table->index('user_id');
            $table->index('user_guid');
            $table->index('status');
            $table->index('opened_at');
            $table->index(['status', 'user_id']);
        });

        Schema::table('orders.orders', function (Blueprint $table): void {
            $table->foreignId('shift_id')->nullable()->after('order_number');
            $table->foreignId('user_id')->nullable()->after('shift_id');

            $table
                ->foreign('shift_id')
                ->references('id')
                ->on('orders.shifts')
                ->nullOnDelete();
            $table
                ->foreign('user_id')
                ->references('id')
                ->on('authentication.users')
                ->nullOnDelete();
            $table->index('shift_id');
            $table->index('user_id');
            $table->index(['shift_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders.orders', function (Blueprint $table): void {
            $table->dropForeign(['shift_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['shift_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['shift_id', 'status']);
            $table->dropColumn(['shift_id', 'user_id']);
        });

        Schema::dropIfExists('orders.shifts');
    }
};
