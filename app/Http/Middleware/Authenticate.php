<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // For API routes, return null to trigger JSON response instead of redirect
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }
        
        // For web routes, redirect to login (but we don't have login route yet)
        // return route('login');
        return null;
    }
}
