<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'username' => 'required|string|min:8|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        auth()->login($user);
        // $request->session()->regenerate();

        return response()->json(['user' => $user]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required_without:username|string|email',
            'username' => 'required_without:email|string',
            'password' => 'required|string',
        ]);

        // Find user by either email or username
        $user = User::where(function ($query) use ($request) {
            $query->where('email', $request->email)
                ->orWhere('username', $request->username);
        })->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }


        $token = $request->remember ? $user->createToken('restlux', ['*'])->plainTextToken : $user->createToken('restlux')->plainTextToken;

        return response()->json(['message' => 'Logged in', 'token' => $token]);
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }
}