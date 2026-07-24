<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the old "name LIKE 'Unmapped %'" convention for flagging auto-created
 * records. The real HR API sends actual names (not opaque numeric IDs), so an
 * auto-created Company/Designation now gets its real name immediately — there's
 * no placeholder text left to detect. An explicit flag, cleared when an Admin
 * saves the record, is the correct replacement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('needs_review')->default(false)->after('is_active');
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('needs_review')->default(false)->after('is_active');
        });
        Schema::table('designations', function (Blueprint $table) {
            $table->boolean('needs_review')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('needs_review');
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('needs_review');
        });
        Schema::table('designations', function (Blueprint $table) {
            $table->dropColumn('needs_review');
        });
    }
};
