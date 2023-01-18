<?php

namespace Salt\Firebase\Services;

use Exception;
use Kreait\Firebase\Auth\UserRecord;
use Kreait\Firebase\Contract;
use Kreait\Firebase\Exception\Auth\FailedToVerifySessionCookie;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class FirebaseService
{
    /**
     * @var Factory
     * @var [type]
     */
    protected $factory;

    /**
     * @var Auth
     * @var [type]
     */
    protected $auth;

    protected $firebase;

    public function __construct()
    {
        $serviceAccount = ServiceAccount::fromValue([
            'type' => 'service_account',
            'project_id' => config('salt-firebase.firebase.project_id'),
            'private_key_id' => config('salt-firebase.firebase.private_key_id'),
            'private_key' => config('salt-firebase.firebase.private_key'),
            'client_email' => config('salt-firebase.firebase.client_email'),
            'client_id' => config('salt-firebase.firebase.client_id'),
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => config('salt-firebase.firebase.client_x509_cert_url'),
        ]);

        $this->factory = (new Factory)
            ->withServiceAccount($serviceAccount);

        $this->buildAuth();
    }

    public function buildAuth()
    {
        $this->auth = app()->make(Contract\Auth::class, ['factory' => $this->factory]);
    }

    /**
     * Update Firebase user record
     *
     * @param  string  $uid
     * @param  array  $properties
     * @return UserRecord
     */
    public function updateUser(string $uid, array $properties): UserRecord
    {
        $user = $this->auth->updateUser($uid, $properties);

        return $user;
    }

    /**
     * Create Firebase user with email & password
     *
     * @param  string  $email
     * @param  string  $password
     * @return UserRecord
     */
    public function createUserWithEmailAndPassword(string $email, string $password): UserRecord
    {
        $user = $this->auth->createUserWithEmailAndPassword($email, $password);

        return $user;
    }

    /**
     * Get user from Auth Token
     *
     * @param  string  $token
     * @return UserRecord|void
     */
    public function getUserFromAuthToken(string $token)
    {
        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token);
        } catch (FailedToVerifyToken $e) {
            return null;
        }

        $uid = $verifiedIdToken->claims()->get('sub');

        $user = $this->auth->getUser($uid);

        return $user;
    }

    /**
     * Get Firebase user by email
     *
     * @param  string  $email
     * @return UserRecord|null
     */
    public function getUserByEmail(string $email)
    {
        try {
            $user = $this->auth->getUserByEmail($email);
        } catch (Exception $e) {
            return null;
        }

        return $user;
    }

    /**
     * Get Firebase user by ID
     *
     * @param  string  $uid
     * @return UserRecord|null
     */
    public function getUserByID(string $uid)
    {
        try {
            $user = $this->auth->getUser($uid);
        } catch (Exception $e) {
            return null;
        }

        return $user;
    }

    /**
     * Creates a session string
     * for use with middleware.
     *
     * Relies on session.lifetime
     * to set ttl, which should be set
     * to 1 hour usually, to match the
     * validity period of a Firebase JWT.
     *
     * It may be slightly more than 1 hour
     * to give the client time to refresh
     * the token and resume the session silently.
     *
     * @param  string  $token
     * @return string
     */
    public function createSessionString(string $token)
    {
        return $this->auth->createSessionCookie($token, config('session.lifetime') * 60);
    }

    /**
     * Verifies a session string
     * for use with middleware
     *
     * @param  string  $token
     * @return bool
     */
    public function sessionTokenIsValid(string $session_token): bool
    {
        try {
            $this->auth->verifySessionCookie($session_token);

            return true;
        } catch (FailedToVerifySessionCookie $e) {
            return false;
        }
    }

    /**
     * Generate a reset password link for use in a
     * custom email.
     */
    public function getPasswordResetLink(string $email)
    {
        $continue_url = session('redirect_url') ?? config('app.url');
        try {
            $link = $this->auth->getPasswordResetLink($email,[
                'continueUrl' => $continue_url
            ]);
        } catch (Exception $e) {
            logger($e->getMessage());
            return false;
        }

        return $link;
    }

    /**
     * Send a password reset email, managed by
     * Firebase console.
     */
    public function requestPasswordResetEmail(string $email)
    {
        return $this->auth->sendPasswordResetLink($email);
    }

    /**
     * Confirm password reset
     */
    public function confirmPasswordReset(string $oob_code, string $email, $invalidate_previous_sessions = true): bool
    {
        try {
            return $this->auth->confirmPasswordReset($oob_code, $email, $invalidate_previous_sessions);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create custom token
     */
    public function createCustomToken(string $uid)
    {

        try {
            return $this->auth->createCustomToken($uid);
        } catch (Exception $e) {
            return false;
        }

    }

    /**
     * Update firebase user password
     *
     * @param string $uid
     * @param string $password
     * @return boolean
     */
    public function updateUserPassword(string $uid, string $password)
    {

        try {
            $this->auth->changeUserPassword($uid, $password);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function invalidateSession(string $uid): bool
    {
        try {
            $this->auth->revokeRefreshTokens($uid);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
