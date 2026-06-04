<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product.categories', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('description');
        });

        Schema::table('product.groups', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('description');
        });

        Schema::table('product.products', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('product.products', function (Blueprint $table): void {
            $table->dropColumn('image');
        });

        Schema::table('product.groups', function (Blueprint $table): void {
            $table->dropColumn('image');
        });

        Schema::table('product.categories', function (Blueprint $table): void {
            $table->dropColumn('image');
        });
    }
};
