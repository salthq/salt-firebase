<?php

namespace Salt\Firebase\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
    public function handle(Request $request, Closure $next)
    {
        $redirect_url = route(config('salt-firebase.routes.login_refresh'));

        if (Auth::check() && ! $this->auth->sessionTokenIsValid()) {
            $this->auth->logout();
            $request->session()->flash('redirect_url', $request->url());
            return redirect($redirect_url);
        }

        return $next($request);
    }
}
