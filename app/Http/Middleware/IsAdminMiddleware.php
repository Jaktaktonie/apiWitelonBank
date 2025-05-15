<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->administrator) { // Zakładamy, że model Uzytkownik ma pole 'administrator'
            return $next($request);
        }

        return response()->json(['message' => 'Brak uprawnień administratora.'], 403);
    }
}
