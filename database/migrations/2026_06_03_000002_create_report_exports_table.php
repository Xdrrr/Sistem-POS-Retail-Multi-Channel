<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('guid')->unique();
            $table->string('type', 50);
            $table->string('status', 30)->default('queued');
            $table->json('filters')->nullable();
            $table->string('format', 10)->default('csv');
            $table->string('file_path')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('requested_by');
            $table->index('expired_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
