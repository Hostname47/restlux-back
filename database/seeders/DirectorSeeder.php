<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

        $user->syncRoles(Role::all());
        /**
         * Please remember that in Roles and Permissions seeder, you assign all permissiomns to director role,
         * so you don't have to assign permissions to him again
         */
    }
}
