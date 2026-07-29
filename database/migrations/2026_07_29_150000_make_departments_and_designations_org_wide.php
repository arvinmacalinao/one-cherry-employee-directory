<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Department (HR's "usergroup"/ug_id) and Designation (d_id) are shared,
 * organization-wide master data, not per-company records — confirmed by the
 * client: "there should only be one Sales, one IT, one HR, regardless of
 * company." An employee's company comes solely from their own company_id;
 * it was never a property of the department/designation itself.
 *
 * This reverses an earlier (uncommitted, never-shipped) attempt to scope
 * hr_ref_id uniqueness to (company_id, hr_ref_id) — that was built on the
 * wrong assumption that HR's reuse of numeric ug_id/d_id values across
 * companies meant per-company duplicates were intentional. It doesn't:
 * HR reuses those IDs because the department/designation genuinely is the
 * same one, shared by every company.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // MySQL requires the FK dropped before the index it depends on.
            $table->dropForeign(['company_id']);
            $table->dropUnique('departments_company_id_name_unique');
            $table->dropColumn('company_id');
            $table->unique('name');
        });

        Schema::table('designations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropUnique('designations_company_id_name_unique');
            $table->dropColumn('company_id');
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_name_unique');
            $table->foreignId('company_id')->nullable()->after('hr_ref_id')->constrained()->cascadeOnDelete();
            $table->unique(['company_id', 'name']);
        });

        Schema::table('designations', function (Blueprint $table) {
            $table->dropUnique('designations_name_unique');
            $table->foreignId('company_id')->nullable()->after('hr_ref_id')->constrained()->cascadeOnDelete();
            $table->unique(['company_id', 'name']);
        });
    }
};
