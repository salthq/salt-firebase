<?php

namespace Salt\Firebase\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Salt\Firebase\Exceptions\AuthServiceException;
use Salt\Firebase\Http\Controllers\Controller;
use Salt\Firebase\Services\AuthService;

/**
 * This controller can be extended or entirely
 * replaced but serves as a guide for using
 * the services defined in this package.
 *
 * To use this controller, ensure that routes
 * are correctly configured in the salt-firebase.php
 * config file.
 */
class FirebaseAuthController extends Controller
{
    public AuthService $firebase_auth_service;

    public function __construct(AuthService $firebase_auth_service)
    {
        $this->firebase_auth_service = $firebase_auth_service;
    }

    public function index(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->to(route(config('salt-firebase.routes.login')));
        }
    }

    /**
     * Expects a token to be passed with the request
     * which verifies the token is valid on Firebase
     * before logging the user in.
     */
    public function verifySSOToken(Request $request)
    {
        if (Auth::check()) {
            return redirect()->to(route(config('salt-firebase.routes.login_success')));
        }

        // Check token exists
        if (! $request->input('token')) {
            return redirect()->to(route(config('salt-firebase.routes.login_error')))->with('error', 'Token not provided');
        }

        // Log the user in or redirect to login
        try {
            if ($request->get('reg')) {
                $this->firebase_auth_service->processSignUpFromToken($request->input('token'));
            } else {
                $this->firebase_auth_service->processLoginFromToken($request->input('token'), config('salt-firebase.allow_login_signup'));
            }
        } catch (AuthServiceException $e) {
            return redirect()->to(route(config('salt-firebase.routes.login_error')))->with('error', $e->getMessage());
        }

        return redirect()->to(route(config('salt-firebase.routes.login_success')));
    }

    /**
     * Checks that a token is still valid
     */
    public function verifySSOTokenInBackground(Request $request)
    {
        // Check token has been passed
        if (! $request->input('token')) {
            return response()->json([
                'error' => 'Token not provided',
            ], 400);
        }

        try {
            $this->firebase_auth_service->processLoginFromToken($request->input('token'));
        } catch (AuthServiceException $e) {
            return response()->json([
                'error' => 'Could not resume the session.',
            ], 403);
        }

        return response()->json();
    }

    /**
     * Logs the user out and redirects
     * to login url.
     */
    public function logout(Request $request)
    {
        $this->firebase_auth_service->logout();

        return redirect()->to(route(config('salt-firebase.routes.login')));
    }

    /**
     * This method is usually overriden
     * using a different view to display
     * the error.
     */
    public function error(Request $request)
    {
        return response()->json([
            'error_message' => 'Sorry there was an issue logging you in, please try again or contact support',
        ]);
    }
}
