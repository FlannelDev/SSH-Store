<?php

namespace App\Plugins\ShadowStore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectToStore
{
    public function handle(Request $request, Closure $next)
    {
        // If user is not authenticated and trying to access root, redirect to store
        if (!auth()->check() && $request->is('/')) {
            return redirect('/store');
        }

        return $next($request);
    }
}
