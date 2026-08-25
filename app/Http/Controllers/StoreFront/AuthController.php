<?php

namespace App\Http\Controllers\StoreFront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('StoreFront/Auth/Login');
    }

    public function login(LoginRequest $request)
    {
        $request->authenticate();

        $user = Auth::user();

        if (! $user->status) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('account.inactive');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function showRegister()
    {
        return Inertia::render('StoreFront/Auth/Register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'mobile' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:2000',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => 3,
            'user_type' => 'wholeseller',
            'status' => 0,
        ]);

        UserDetail::create([
            'user_id' => $user->id,
            'mobile' => $validated['mobile'] ?? null,
            'company' => $validated['company'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        return redirect()->route('account.inactive');
    }

    public function showInactive()
    {
        return Inertia::render('StoreFront/Auth/AccountInactive');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
