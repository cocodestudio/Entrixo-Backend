<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        User::create([
            'name' => 'Admin Panel',
            'email' => 'admin@entrixo.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_setup_completed' => true,
        ]);
    }
}