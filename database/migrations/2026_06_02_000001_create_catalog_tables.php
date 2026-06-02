<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_groups', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->foreignId('category_id');
            $table->foreignId('group_id');
            $table->string('name', 150)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table
                ->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->restrictOnDelete();
            $table
                ->foreign('group_id')
                ->references('id')
                ->on('product_groups')
                ->restrictOnDelete();
            $table->index(['category_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_groups');
        Schema::dropIfExists('categories');
    }
};
