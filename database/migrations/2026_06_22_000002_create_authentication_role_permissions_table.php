<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authentication.role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id');
            $table->uuid('permission_guid');
            $table->timestamps();

            $table
                ->foreign('role_id')
                ->references('id')
                ->on('authentication.roles')
                ->cascadeOnDelete();

            $table
                ->foreign('permission_guid')
                ->references('guid')
                ->on('authentication.permissions')
                ->cascadeOnDelete();

            $table->unique(['role_id', 'permission_guid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authentication.role_permissions');
    }
};
