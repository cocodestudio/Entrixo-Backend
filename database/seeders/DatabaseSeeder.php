<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        User::create([
            'name' => 'Super Admin',
            'email' => 'cocodestudio.org@gmail.com',
            'password' => Hash::make('Abuz@123'),
            'role' => 'admin',
            'is_setup_completed' => true,
        ]);
    }
}