<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The real HR API sends null email for a large share of employees (a data-quality
 * gap on HR's side, not something OCED should paper over — see the sync warning
 * "skipping unusable employee record"). Per the decision to make email optional
 * rather than fabricate a placeholder, employees without a corporate email on
 * file still import; they just don't get a mailto link until HR fills it in.
 * MySQL's unique index still enforces no duplicates among non-null values.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
