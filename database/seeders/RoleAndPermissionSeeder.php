<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $directorRole = Role::create(['name' => 'Director', 'guard_name' => 'api']);
        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'api']);
        $clientManagerRole = Role::create(['name' => 'Client Manager', 'guard_name' => 'api']);
        $productManagerRole = Role::create(['name' => 'Product Manager', 'guard_name' => 'api']);
        
        Permission::create(['name' => 'Manage Admins', 'guard_name' => 'api']);

        $manageEmployees = Permission::create(['name' => 'Manage Employees', 'guard_name' => 'api']);
        
        $viewProducts = Permission::create(['name' => 'View Products', 'guard_name' => 'api']);
        $createProducts = Permission::create(['name' => 'Create Products', 'guard_name' => 'api']);
        $editProducts = Permission::create(['name' => 'Edit Products', 'guard_name' => 'api']);
        $deleteProducts = Permission::create(['name' => 'Delete Products', 'guard_name' => 'api']);
        $viewOrders = Permission::create(['name' => 'View Orders', 'guard_name' => 'api']);
        $createOrders = Permission::create(['name' => 'Create Order', 'guard_name' => 'api']);
        $editOrders = Permission::create(['name' => 'Edit Orders', 'guard_name' => 'api']);
        $deleteOrders = Permission::create(['name' => 'Delete Orders', 'guard_name' => 'api']);

        $manageCategories = Permission::create(['name' => 'Manage Categories', 'guard_name' => 'api']);

        // Assign permissions
        $directorRole->syncPermissions(Permission::all());
        $adminRole->givePermissionTo([$manageEmployees, $viewProducts, $createProducts, $editProducts, $deleteProducts, $viewOrders, $createOrders, $editOrders, $deleteOrders]);
        $clientManagerRole->givePermissionTo([$viewOrders, $createOrders, $editOrders, $deleteOrders]);
        $productManagerRole->givePermissionTo([$viewProducts, $createProducts, $editProducts, $deleteProducts, $manageCategories]);
    }

}
