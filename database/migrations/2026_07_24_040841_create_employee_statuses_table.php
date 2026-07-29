<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employment status is a synced lookup table, not an OCED-owned enum — see
 * architecture-plan.md §2.5. HR's employment_status.{id, name} is matched
 * ID-first against hr_ref_id, auto-creating a row the first time an unseen
 * es_id appears. OCED never translates or buckets these values.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('hr_ref_id')->unique()->comment("HR's es_id, primary match key");
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_statuses');
    }
};
