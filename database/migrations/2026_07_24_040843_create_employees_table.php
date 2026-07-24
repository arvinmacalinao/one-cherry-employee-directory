<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // HR-controlled — overwritten on every sync. See HrSyncService.
            $table->string('employee_id')->unique()->comment("HR's employee_code, immutable sync key");
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email')->unique()->comment('Corporate email');
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('designation_id')->constrained()->restrictOnDelete();
            $table->foreignId('immediate_supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('employment_status', ['active', 'on_leave', 'resigned', 'inactive'])->default('active');
            $table->date('date_hired')->nullable();
            $table->date('date_regularized')->nullable();
            $table->date('date_separated')->nullable();
            $table->unsignedTinyInteger('job_level')->nullable()->comment('Stored only — not surfaced in v1 UI');

            // Directory-side bookkeeping.
            $table->enum('source', ['hr_sync', 'manual'])->default('hr_sync');
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'department_id']);
            $table->index('employment_status');
            $table->fullText(['first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
