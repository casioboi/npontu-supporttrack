<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = env('SUPPORTTRACK_ADMIN_EMAIL');
        $adminPassword = env('SUPPORTTRACK_ADMIN_PASSWORD');
        if ($adminEmail && $adminPassword) {
            User::updateOrCreate(['email' => $adminEmail], ['name' => env('SUPPORTTRACK_ADMIN_NAME', 'SupportTrack Administrator'), 'password' => Hash::make($adminPassword), 'role' => 'admin', 'is_active' => true]);
        }
    }
}
