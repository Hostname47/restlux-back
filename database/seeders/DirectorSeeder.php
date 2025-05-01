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
        // Above line is enough, but if there is a permission not attached to any role, the director must be 
        // able to do the task restricted by this permission.
        $user->syncPermissions(Permission::all());
    }
}
