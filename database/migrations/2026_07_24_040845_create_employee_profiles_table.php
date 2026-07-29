<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();

            // Entirely directory-owned — sync never touches this table. See architecture-plan.md §2.5, §7.
            $table->date('birthday')->nullable()->comment('Not provided by HR — required for Birthday Celebrants, see architecture-plan.md §3.2');
            $table->string('viber_number', 30)->nullable();
            $table->foreignId('office_location_id')->nullable()->constrained('office_locations')->nullOnDelete();
            $table->text('about_me')->nullable();

            $table->timestamps();

            // Photo: spatie/laravel-medialibrary collection on the Employee model.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
