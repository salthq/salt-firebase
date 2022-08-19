<?php

namespace Salt\Firebase\Services;

use Illuminate\Support\Facades\Auth;
use Salt\Firebase\Exceptions\AuthServiceException;
use Salt\Firebase\Models\User;
use Salt\Firebase\Repositories\CustomUserRepository;

class AuthService
{
    public function __construct(FirebaseService $firebase, CustomUserRepository $user_repository)
    {
        $this->firebase = $firebase;
        $this->user_repository = $user_repository;
    }

    /**
     * Process the login for a given user uid.
     */
    public function processLoginFromToken(string $token = null): User
    {
        $firebase_user = $this->firebase->getUserFromAuthToken($token);

        if (! $firebase_user) {
            throw new AuthServiceException('User could not be verified using token.');
        }

        $user = User::where('uid', $firebase_user->uid)->first();

        if (! $user) {
            throw new AuthServiceException('User could not be found.');
        }

        $this->login($user, $token);

        return $user;
    }

    /**
     * Process the login for a given user uid.
     */
    public function login($user, string $token): void
    {
        Auth::login($user, true);

        session(['firebase_id_token' => $this->firebase->createSessionString($token)]);
    }

    /**
     * Process the signup from a token
     */
    public function processSignUpFromToken(string $token = null): User
    {
        $firebase_user = $this->firebase->getUserFromAuthToken($token);

        if (! $firebase_user) {
            throw new AuthServiceException('User could not be verified using token.');
        }

        $allowed_email_domains = array_merge(config('salt-firebase.allowed_emails'), config('auth.admin_emails') ?? []);

        if (! in_array(substr($firebase_user->email, strpos($firebase_user->email, '@')), $allowed_email_domains)) {
            throw new AuthServiceException('Sign up is not permitted for your email address.');
        }

        $user = $this->user_repository->upsertOrCreateUserByEmail(
            $firebase_user->email,
            ['name' => $firebase_user->displayName]
        );

        $this->login($user, $token);

        return $user;
    }

    /**
     * Log the user out, remove related session data.
     */
    public function logout(): void
    {
        Auth::logout();
        session()->forget('firebase_id_token');
    }

    /**
     * Process the login for a given user uid.
     */
    public function sessionTokenIsValid(): bool
    {
        return $this->firebase->sessionTokenIsValid(session()->get('firebase_id_token') ?? '');
    }
}
