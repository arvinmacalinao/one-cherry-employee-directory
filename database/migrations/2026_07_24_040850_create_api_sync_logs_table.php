<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('sync_type', ['manual', 'scheduled']);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['success', 'partial', 'failed'])->nullable();
            $table->unsignedInteger('records_imported')->default(0);
            $table->unsignedInteger('records_updated')->default(0);
            $table->unsignedInteger('records_transferred')->default(0);
            $table->unsignedInteger('records_deactivated')->default(0);
            $table->json('errors')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sync_logs');
    }
};
