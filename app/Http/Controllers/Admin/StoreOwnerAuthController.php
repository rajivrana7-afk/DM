<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StoreOwnerAuthController extends Controller
{
    public function showLogin()
    {
        return view('store.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('store_owner')->attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();
            return redirect()->route('store.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function showRegister()
    {
        return view('store.auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:store_owners',
            'phone'    => 'required|string|max:20',
            'password' => 'required|min:8|confirmed',
        ]);

        StoreOwner::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('store.login')->with('success', 'Account created. Please login.');
    }

    public function logout(Request $request)
    {
        Auth::guard('store_owner')->logout();
        $request->session()->invalidate();
        return redirect()->route('store.login');
    }
}
