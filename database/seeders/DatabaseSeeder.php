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
        User::factory()->create([
            'name' => 'admin user',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'isAdmin' => true,
            'email_verified_at' => now(),
            'points_balance' => 10000,
            'wallet_balance' => 10000,
        ]);
        
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'isAdmin' => false,
            'email_verified_at' => now(),
            'points_balance' => 0,
            'wallet_balance' => 0,
        ]);
        User::factory(10)->create();
    }
}
