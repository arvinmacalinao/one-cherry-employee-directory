<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The printed "Local Phone Numbers" sheets, per building. Deliberately not tied to
 * employees: lines are shared ("JOYCE / ERICK / YEL"), some are rooms ("CCTV ROOM"),
 * and locals carry free-form prefixes like "(20) 2500" or "1907 / 2005".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trunkline')->nullable();
            $table->string('mobile')->nullable();
            $table->date('last_updated_on')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('phone_floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phone_building_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('access_code')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('phone_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phone_floor_id')->constrained()->cascadeOnDelete();
            $table->string('department')->nullable();
            $table->string('name')->nullable();
            $table->string('local')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_entries');
        Schema::dropIfExists('phone_floors');
        Schema::dropIfExists('phone_buildings');
    }
};
