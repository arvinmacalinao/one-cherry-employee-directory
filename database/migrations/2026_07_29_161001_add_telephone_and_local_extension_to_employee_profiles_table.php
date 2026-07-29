<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telephone/local extension are directory-owned, same as Viber — HR doesn't
 * track office phone/extension. Added back after the client flagged them as
 * the most important missing profile field for internal use.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('telephone', 30)->nullable()->after('viber_number');
            $table->string('local_extension', 10)->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn(['telephone', 'local_extension']);
        });
    }
};
