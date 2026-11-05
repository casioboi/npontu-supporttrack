<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password
            && ! $request->routeIs('profile', 'profile.password', 'logout')) {
            return redirect()->route('profile')->with('password_change_required', true);
        }

        return $next($request);
    }
}
