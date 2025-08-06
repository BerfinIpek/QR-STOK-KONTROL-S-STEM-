<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
       if (auth()->check() && auth()->user()->rol === 'admin') {
            return $next($request);
        }

        return response()->json(['error' => 'Admin yetkisi gerekli'], 403);
    }
}
