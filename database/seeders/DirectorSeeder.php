<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DirectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'fullname' => 'Restlux Director',
            'username' => 'Hostname47',
            'email' => 'director@restlux.com',
            'password' => Hash::make('root'), // This is a temporary password, and need to be changed
        ]);

        $user->assignRole('director');
    }
}
