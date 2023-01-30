<?php

namespace Salt\Firebase\Services;

use Illuminate\Support\Facades\Auth;
use Salt\Firebase\Exceptions\AuthServiceException;
use Salt\Firebase\Models\User;
use Salt\Firebase\Repositories\CustomUserRepository;

class AuthService
{
    public FirebaseService $firebase;

    public CustomUserRepository $user_repository;

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
            // Configured email domains are allowed to sign up during login
            if ($firebase_user->email && self::emailIsAllowed($firebase_user->email)) {
                $user = $this->processSignUpFromToken($token);
            } else {
                throw new AuthServiceException('User could not be found.');
            }
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
    public function processSignUpFromToken(string $token = null, $user_data = []): User
    {
        $firebase_user = $this->firebase->getUserFromAuthToken($token);

        if (! $firebase_user) {
            throw new AuthServiceException('User could not be verified using token.');
        }

        if (! self::emailIsAllowed($firebase_user->email)) {
            throw new AuthServiceException('Sign up is not permitted for your email address.');
        }

        $user = $this->user_repository->upsertOrCreateUserByEmail(
            $firebase_user->email,
            array_merge([
                'name' => $firebase_user->displayName,
            ], $user_data)
        );

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

    /**
     * Check if email is allowed for login on signup
     */
    public static function emailIsAllowed(string $email): bool
    {
        $allowed_email_domains = array_merge(config('salt-firebase.admin_emails'), config('auth.admin_emails') ?? []);

        return in_array(substr($email, strpos($email, '@')), $allowed_email_domains);
    }
}
