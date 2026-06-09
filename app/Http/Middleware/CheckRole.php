<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Kick them out if they aren't logged in, or if their role doesn't match
        if (!Auth::check() || Auth::user()->role !== $role) {
            abort(403, 'Unauthorized access.'); 
        }

        return $next($request);
    }
}