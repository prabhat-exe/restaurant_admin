<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RestaurantAuthController extends Controller
{
    public function showRegister()
    {
        return view('restaurant.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',

            'email' => 'required|string|email|max:255|unique:restaurants,email',

            'phone' => 'required|digits_between:10,15|unique:restaurants,phone',

            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],

            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|digits_between:4,10',
        ]);

        $restaurant = Restaurant::create([
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'phone' => trim($validated['phone']),
            'password' => Hash::make($validated['password']),
            'address' => trim($validated['address']),
            'city' => trim($validated['city']),
            'state' => trim($validated['state']),
            'pincode' => trim($validated['pincode']),
        ]);

        // Auto login after registration
        Auth::guard('restaurant')->login($restaurant);

        return redirect()->route('restaurant.dashboard')
            ->with('success', 'Registered Successfully!');
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
