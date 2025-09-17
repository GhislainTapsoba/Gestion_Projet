<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Administrateur',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Chef de projet
        User::create([
            'name' => 'Jean Dupont',
            'email' => 'chef@example.com',
            'password' => Hash::make('password'),
            'role' => 'chef_projet',
        ]);

        // Employés
        User::create([
            'name' => 'Marie Martin',
            'email' => 'marie@example.com',
            'password' => Hash::make('password'),
            'role' => 'employe',
        ]);

        User::create([
            'name' => 'Pierre Durand',
            'email' => 'pierre@example.com',
            'password' => Hash::make('password'),
            'role' => 'employe',
        ]);

        User::create([
            'name' => 'Sophie Bernard',
            'email' => 'sophie@example.com',
            'password' => Hash::make('password'),
            'role' => 'employe',
        ]);
    }
}
