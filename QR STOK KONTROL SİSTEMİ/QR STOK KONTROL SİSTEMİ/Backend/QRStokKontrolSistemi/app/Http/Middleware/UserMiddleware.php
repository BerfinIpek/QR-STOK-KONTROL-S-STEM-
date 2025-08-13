<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && in_array(auth()->user()->rol, ['admin', 'user'])) {
            return $next($request);
        }

        return response()->json(['error' => 'Kullanıcı yetkisi gerekli'], 403);
    }
}