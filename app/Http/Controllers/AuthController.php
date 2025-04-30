<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'username' => 'required|string|min:8|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/'
            ],
        ]);

        $user = User::create([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = $user->createToken('front-spa', ["*"], now()->addHours(6))->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required_without:username|string|email',
            'username' => 'required_without:email|string',
            'password' => 'required|string',
            "remember" => "boolean"
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

        $expiration = $request->remember 
            ? now()->addHours(720) 
            : now()->addHours(6);

        $token = $user->createToken('front-spa', ["*"], $expiration)->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function logout(Request $request)
    {
        DB::table('personal_access_tokens')
        ->where('tokenable_id', $request->user()->id)
        ->where('tokenable_type', get_class($request->user()))
        ->delete();

        return response()->json(['message' => 'Logged out']);
    }
}