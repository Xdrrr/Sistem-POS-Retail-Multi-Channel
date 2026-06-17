<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product.inventory_history', function (Blueprint $table): void {
            $table->uuid('user_guid_reff')->nullable()->after('created_by');

            $table
                ->foreign('user_guid_reff')
                ->references('guid')
                ->on('authentication.users');

            $table->index('user_guid_reff');
        });
    }

    public function down(): void
    {
        Schema::table('product.inventory_history', function (Blueprint $table): void {
            $table->dropIndex(['user_guid_reff']);
            $table->dropForeign(['user_guid_reff']);
            $table->dropColumn('user_guid_reff');
        });
    }
};
