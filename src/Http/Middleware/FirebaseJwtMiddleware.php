<?php

namespace Salt\Firebase\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Salt\Firebase\Services\AuthService;

class FirebaseJwtMiddleware
{
    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Check if the user's JWT has expired
     * if the user is currently signed in.
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && ! $this->auth->sessionTokenIsValid()) {
            $this->auth->logout();
        }

        return $next($request);
    }
}
