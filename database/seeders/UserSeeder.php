<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Create member user
        User::create([
            'name' => 'Member User',
            'email' => 'member@example.com',
            'password' => Hash::make('member123'),
            'role' => 'member',
        ]);

        // Create additional test users
        User::factory()->admin()->count(2)->create();
        User::factory()->member()->count(5)->create();
    }
}
