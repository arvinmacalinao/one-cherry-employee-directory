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

            // Not sent by HR at all — directory-owned.
            $table->string('suffix')->nullable();
            $table->string('nickname')->nullable();
            $table->string('gender')->nullable();
            $table->date('birthday')->nullable();
            $table->string('name_pronunciation')->nullable()->comment('Optional phonetic guide');

            // Employee-editable contact details.
            $table->string('personal_email')->nullable();
            $table->string('mobile_number', 30)->nullable();
            $table->string('viber_number', 30)->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('local_extension', 10)->nullable();
            $table->string('office_seat')->nullable()->comment('Desk / seat identifier');
            $table->foreignId('office_location_id')->nullable()->constrained('office_locations')->nullOnDelete();

            // Employee-editable "about" content. Skills are normalized — see skills/employee_skill tables.
            $table->text('about_me')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('linkedin_url')->nullable();

            // Emergency contact (optional per spec).
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();

            $table->timestamps();

            // Photo, cover banner: spatie/laravel-medialibrary collections on the Employee model.
            // QR code: generated on demand by QrCodeService, not persisted.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
