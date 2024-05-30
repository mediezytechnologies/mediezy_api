<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('backend.admin_login.admin_login_page');
    }

    public function login(Request $request)
    {
        // Validation rules
        $rules = [
            'email' => 'required',
            'password' => 'required|string'
        ];

      
        $request->validate($rules);
    
        // find user email in users table
        $user = User::where('email', $request->email)
           // ->where('user_role', 1) 
            ->first();

        if(!$user) {
            return back()->withErrors([
                'email' => 'Invalid email address',
            ]);
        }
       // print_r($user->password);exit;
        if ($user && Hash::check($request->password, $user->password)) {
          
            return redirect()->route('dashboard');
        }

        else {

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ]);
    }
    }


    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Your registration logic here

        return redirect()->route('login')->with('success', 'Registration successful!');
    }

    public function logout()
    {
        Auth::logout();

        return redirect('/');
    }
}
