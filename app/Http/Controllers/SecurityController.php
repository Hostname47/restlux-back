<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class SecurityController extends Controller
{
    public function grant_permission(Request $request)
    {
        if (!$request->user()->can('Manage Admins')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            "user_id" => "required|exists:users,id",
            "permission_id" => "required|exists:permissions,id",
        ]);

        $user = User::findOrFail($data['user_id']);
        $permission = Permission::findOrFail($data['permission_id']);
        $user->givePermissionTo($permission);

        return response()->json([
            'message' => 'Permission given successfully',
            'user' => $user->only(['id', 'name', 'email']),
            'permission' => $permission->only(['id', 'name']),
        ], 201);
    }

    public function revoke_permission(Request $request)
    {
        if (!$request->user()->can('Manage Admins')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        $data = $request->validate([
            "user_id" => "required|exists:users,id",
            "permission_id" => "required|exists:permissions,id",
        ]);
    
        $user = User::findOrFail($data['user_id']);
        $permission = Permission::findOrFail($data['permission_id']);
        $user->revokePermissionTo($permission);
    
        return response()->json([
            'message' => 'Permission revoked successfully',
            'user' => $user->only(['id', 'name', 'email']),
            'permission' => $permission->only(['id', 'name']),
        ], 200);
    }

    public function grant_role(Request $request)
    {
        if (!$request->user()->can('Manage Admins')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            "user_id" => "required|exists:users,id",
            "role_id" => "required|exists:roles,id",
        ]);

        $user = User::findOrFail($data['user_id']);
        $role = Role::findOrFail($data['role_id']);
        $user->assignRole($role);

        return response()->json([
            'message' => 'Role assigned successfully',
            'user' => $user->only(['id', 'name', 'email']),
            'role' => $role->only(['id', 'name']),
        ], 201);
    }

    public function revoke_role(Request $request)
    {
        if (!$request->user()->can('Manage Admins')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        $data = $request->validate([
            "user_id" => "required|exists:users,id",
            "role_id" => "required|exists:roles,id",
        ]);
    
        $user = User::findOrFail($data['user_id']);
        $role = Role::findOrFail($data['role_id']);
        $user->removeRole($role);
    
        return response()->json([
            'message' => 'Role revoked successfully',
            'user' => $user->only(['id', 'name', 'email']),
            'role' => $role->only(['id', 'name']),
        ], 200);
    }

    public function create_employee_with_roles(Request $request)
    {
        if (!$request->user()->can('Manage Admins')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'username' => 'required|string|min:8|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/',
                'confirmed'
            ],
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => [
                'integer',
                Rule::exists('roles', 'id'),
            ],
        ]);
    
        $user = User::create([
            'fullname' => $validated['fullname'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $roles = Role::whereIn('id', $validated['role_ids'])->get();
        $user->syncRoles($roles);
    
        return response()->json([
            'message' => 'Employee created and roles assigned successfully.',
            'user' => $user->only(['id', 'fullname', 'username', 'email']),
            'roles' => $roles->map->only(['id', 'name']),
        ], 201);
    }
}
