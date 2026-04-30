<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@clearboatbahamas.com'],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Super Admin',
                'password' => bcrypt('Clearwater2026!'),
                'role' => 'super_admin',
            ]
        );
    }
}
