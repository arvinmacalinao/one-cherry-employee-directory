<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Building B's access codes, read off the printed sheet after the first seed.
 * Only fills floors that still have none, so codes an admin already entered
 * (or floors they renamed) are left alone.
 */
return new class extends Migration
{
    protected array $codes = [
        '7th Floor' => '27',
        '8th Floor' => '29',
        '9th Floor' => '20',
        '11th Floor' => '29',
    ];

    public function up(): void
    {
        $buildingId = DB::table('phone_buildings')->where('name', 'Building B')->value('id');

        if (! $buildingId) {
            return;
        }

        foreach ($this->codes as $floor => $code) {
            DB::table('phone_floors')
                ->where('phone_building_id', $buildingId)
                ->where('name', $floor)
                ->whereNull('access_code')
                ->update(['access_code' => $code]);
        }
    }

    public function down(): void
    {
        // Data fill only — codes may since have been edited, so nothing to undo safely.
    }
};
