<?php

namespace App\Plugins\ShadowStore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectGuestsToStore
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only redirect from root path for guests who are NOT authenticated
        // Check multiple auth methods to ensure we catch logged in users
        if ($request->path() === '/' && !Auth::check() && !$request->user()) {
            // Also check if there's a valid session
            if (!session()->has('auth') && !session()->has('login_web_' . sha1('Illuminate\Auth\SessionGuard'))) {
                return redirect('/store');
            }
        }

        return $next($request);
    }
}
