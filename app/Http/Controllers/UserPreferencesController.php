<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserPreferencesController extends Controller
{
    /**
     * Update the header position preference for the authenticated user.
     */
    public function updateHeaderPosition(Request $request)
    {
        $request->validate([
            'position' => ['required', Rule::in(['top', 'bottom', 'left', 'right'])],
        ]);

        $user = Auth::user();
        $user->header_position = $request->position;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Posición del header actualizada correctamente',
            'position' => $request->position,
        ]);
    }
}
