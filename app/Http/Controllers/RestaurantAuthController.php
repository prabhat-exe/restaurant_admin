<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RestaurantAuthController extends Controller
{
    
    public function showAdminLogin()
    {
        return view('admin.login');
    }

    public function AdminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('admin')->attempt($credentials)) {
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials',
        ]);
    }

    public function adminLogout()
    {
        Auth::guard('admin')->logout();
        return redirect()->route('admin.login');
    }

    // Show Login
    public function showLogin()
    {
        return view('restaurant.login');
    }

    // Login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('restaurant')->attempt($credentials)) {
            return redirect()->route('restaurant.dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials',
        ]);
    }

    // Logout
    public function logout()
    {
        Auth::guard('restaurant')->logout();
        return redirect()->route('restaurant.login');
    }
}
