<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Muestra el formulario de login
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('modulo1');
        }
        return view('login');
    }

    /**
     * Procesa el login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('modulo1')->with('success', '¡Bienvenido!');
        }

        return back()->withErrors([
            'email' => 'Cuenta no encontrada',
        ])->onlyInput('email');
    }

    /**
     * Muestra el formulario de registro
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('modulo1');
        }
        return view('register');
    }

    /**
     * Procesa el registro
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user', // Los nuevos usuarios son 'user' por defecto
        ]);

        Auth::login($user);

        return redirect()->route('modulo1')->with('success', '¡Cuenta creada exitosamente!');
    }

    /**
     * Cierra sesión
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sesión cerrada correctamente');
    }
}
