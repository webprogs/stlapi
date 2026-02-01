<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@admin.com',
                'password' => 'admin',
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );
    }
}
