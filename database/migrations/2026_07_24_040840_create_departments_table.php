<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('hr_ref_id')->nullable()->unique()->comment("HR's ug_id (user group) — this org's convention for Department");
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // department_head_id added in a follow-up migration once the employees table exists.
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
