<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS product');

        Schema::create('product.categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product.groups', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product.products', function (Blueprint $table): void {
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
                ->on('product.categories')
                ->restrictOnDelete();
            $table
                ->foreign('group_id')
                ->references('id')
                ->on('product.groups')
                ->restrictOnDelete();
            $table->index(['category_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product.products');
        Schema::dropIfExists('product.groups');
        Schema::dropIfExists('product.categories');
        DB::statement('DROP SCHEMA IF EXISTS product');
    }
};
