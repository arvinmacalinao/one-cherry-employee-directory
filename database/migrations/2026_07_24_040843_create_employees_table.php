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

            // HR-controlled — overwritten on every sync. See HrSyncService / architecture-plan.md §2.5.
            $table->string('employee_id')->unique()->comment("HR's employee_code, immutable sync key");
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('username')->nullable()->comment('HR-owned, captured for a future SSO adapter — unused in v1 UI');
            $table->string('email')->unique()->comment('Corporate email');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('immediate_supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('employee_status_id')->nullable()->constrained('employee_statuses')->nullOnDelete()
                ->comment('HR-owned, synced verbatim — not an OCED enum, see architecture-plan.md §2.5');
            $table->boolean('is_active')->default(true)
                ->comment('HR-owned — true iff present in the latest sync run; the sole directory-visibility signal');
            $table->date('date_hired')->nullable();
            $table->date('date_regularized')->nullable();
            $table->date('date_separated')->nullable();

            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'department_id']);
            $table->index('is_active');
            $table->index('immediate_supervisor_id');
            $table->fullText(['first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
