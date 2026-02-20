<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $restaurants = Restaurant::all();
        
        return view('admin.dashboard', compact('restaurants'));
    }

    public function createRestaurant()
    {
        return view('admin.restaurant_create');
    }

    public function storeRestaurant(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:restaurants,email',
            'phone' => 'required|digits_between:10,15|unique:restaurants,phone',
            'password' => 'required|string|min:8',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|digits_between:4,10',
        ]);

        $restaurant = Restaurant::create([
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'phone' => trim($validated['phone']),
            'password' => bcrypt($validated['password']),
            'address' => trim($validated['address']),
            'city' => trim($validated['city']),
            'state' => trim($validated['state']),
            'pincode' => trim($validated['pincode']),
        ]);

        return redirect('/admin/dashboard')->with('success', 'Restaurant added successfully!');
    }
    public function updateRestaurant(Request $request, $id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:restaurants,email,' . $id,
            'phone' => 'required|digits_between:10,15|unique:restaurants,phone,' . $id,
            'address' => 'required|string|max:500',
            'is_active' => 'required|in:0,1',
        ]);

        $restaurant->update([
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'phone' => trim($validated['phone']),
            'address' => trim($validated['address']),
            'is_active' => $validated['is_active'],
        ]);

        return response()->json(['success' => true]);
    }
}
