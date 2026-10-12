<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        return view('account.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('customer')->attempt($data, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('account.index'))->with('status', 'Welcome back!');
        }

        return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('account.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $existing = Customer::where('email', $data['email'])->first();

        // An email that already registered (has a password) can't register again.
        if ($existing && $existing->hasAccount()) {
            return back()->withErrors(['email' => 'An account with this email already exists. Please log in.'])->onlyInput('email', 'name');
        }

        // Guest customer (ordered before, no password) → claim the account; otherwise create new.
        $customer = $existing ?: new Customer();
        $customer->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? $customer->phone,
            'password' => $data['password'],
        ]);
        $customer->save();

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('account.index')->with('status', 'Account created — welcome!');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'You have been logged out.');
    }
}
