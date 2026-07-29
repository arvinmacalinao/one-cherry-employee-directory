<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        Announcement::updateOrCreate(
            ['title' => "Founders' Day — Aug 8, Cherry Tower Atrium"],
            [
                'body' => 'All Group companies invited. RSVP through your department head.',
                'published_at' => now()->subDays(2),
                'expires_at' => now()->addDays(10),
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
        );
    }
}
