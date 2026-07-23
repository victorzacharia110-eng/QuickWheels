<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@quickwheels.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('QuickWheels2024!'),
                'role' => 'superadmin',
                'is_active' => true,
            ]
        );
    }
}
