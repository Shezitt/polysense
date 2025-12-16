<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Debes iniciar sesión');
        }

        if (auth()->user()->role !== 'admin') {
            abort(403, 'No tienes permisos para acceder a esta sección');
        }

        return $next($request);
    }
}
