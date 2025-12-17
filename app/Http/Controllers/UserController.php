<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Muestra la vista de Gestion con la lista de usuarios
     */
    public function index()
    {
        $users = User::all();
        return view('modulo5', compact('users'));
    }

    /**
     * Crea un nuevo usuario
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:user,admin'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role
        ]);

        return redirect()->route('modulo5')->with('success', 'Usuario creado exitosamente');
    }

    /**
     * Elimina un usuario
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        if (auth()->check() && auth()->user()->id == $id) {
            return redirect()->back()->with('error', 'No puedes eliminar tu propio usuario');
        }

        $user->delete();

        return redirect()->route('modulo5')->with('success', 'Usuario eliminado exitosamente');
    }

    /**
     * Cambia el rol de un usuario
     */
    public function changeRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:user,admin'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $user = User::findOrFail($id);
        
        if (auth()->check() && auth()->user()->id == $id) {
            return redirect()->back()->with('error', 'No puedes cambiar tu propio rol');
        }

        $user->role = $request->role;
        $user->save();

        return redirect()->route('modulo5')->with('success', 'Rol actualizado exitosamente');
    }
}
