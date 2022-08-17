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
    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
        $this->login_url = route(config('salt-firebase.routes.login'));
        $this->login_success_url = route(config('salt-firebase.routes.login_success'));
        $this->error_url = route(config('salt-firebase.routes.login_error'));
    }

    public function index()
    {
        if (! Auth::check()) {
            return redirect()->to($this->login_url);
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
            return redirect()->to($this->login_success_url);
        }

        // Check token exists
        if (! $request->input('token')) {
            return redirect()->to($this->error_url);
        }

        // Log the user in or redirect to login
        try {
            if ($request->get('reg')) {
                $this->auth->processSignUpFromToken($request->input('token'));
            } else {
                $this->auth->processLoginFromToken($request->input('token'));
            }
        } catch (AuthServiceException $e) {
            return redirect()->to($this->error_url);
        }

        return redirect()->to($this->login_success_url);
    }

    /**
     * Logs the user out and redirects
     * to login url.
     */
    public function logout()
    {
        $this->auth->logout();

        return redirect()->to($this->login_url);
    }

    /**
     * This method is usually overriden
     * using a different view to display
     * the error.
     */
    public function error()
    {
        return response()->json([
            'error_message' => 'Sorry there was an issue logging you in, please try again or contact support',
        ]);
    }
}
