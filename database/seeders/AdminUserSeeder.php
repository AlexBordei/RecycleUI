<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the initial admin user.
     */
    public function run(): void
    {
        User::create([
            'username' => 'admin',
            'full_name' => 'Administrator',
            'email' => null,
            'password' => Hash::make('password'),
            'is_admin' => true,
            'is_active' => true,
        ]);
    }
}
