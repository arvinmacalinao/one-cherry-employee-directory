<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The printed sheets shade the managers' rows. Lines already entered get the
 * flag by title (managers and VPs); after that it's an explicit editor toggle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_entries', function (Blueprint $table) {
            $table->boolean('is_highlighted')->default(false)->after('local');
        });

        DB::table('phone_entries')
            ->where(fn ($q) => $q
                ->where('department', 'like', '%MANAGER%')
                ->orWhere('department', 'like', '%VICE PRESIDENT%'))
            ->update(['is_highlighted' => true]);
    }

    public function down(): void
    {
        Schema::table('phone_entries', function (Blueprint $table) {
            $table->dropColumn('is_highlighted');
        });
    }
};
