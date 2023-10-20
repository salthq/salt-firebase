<?php

declare(strict_types=1);

namespace Salt\Firebase\Repositories;

use Kreait\Firebase\Auth\UserRecord;
use Salt\Firebase\Models\User;
use Salt\Firebase\Services\FirebaseService as ServicesFirebaseService;

class CustomUserRepository
{
    public function __construct(ServicesFirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * Upsert a firebase user into Laravel
     *
     * @param  UserRecord  $firebase_user
     * @param  array|null  $user_data
     * @return User
     */
    protected function upsertUser(UserRecord $firebase_user, array $user_data = null): User
    {
        // Propagate the email change through to firebase
        if ($user_data[email]) {
            $this->firebase->changeUserEmail($firebase_user->uid, $user_data['email']); 
        }

        // Update the user model with relevant details
        return User::updateOrCreate(['uid' => $firebase_user->uid], [
            'email' => $firebase_user->email ?? $user_data['email'] ?? '',
            'name' => $firebase_user->displayName ?? $user_data['name'] ?? '',
            'uid' => $firebase_user->uid,
        ]);
    }

    /**
     * Upsert or create a user from firebase to Laravel
     *
     * @param  string  $email
     * @param  array|null  $user_data
     * @return User|null
     */
    public function upsertOrCreateUserByEmail(string $email, array $user_data = null)
    {
        // Check if user exists
        $firebase_user = $this->firebase->getUserByEmail($email);

        // If user exists then create and return
        if ($firebase_user) {
            return $this->upsertUser($firebase_user, $user_data);
        }

        // Else create a randomised strong password and return
        $password = password_hash('DummyPassword123', PASSWORD_DEFAULT);

        return $this->createUserWithPassword($email, $password, $user_data);
    }

    /**
     * Update a firebase user
     *
     * @param  string  $uid
     * @param  array|null  $user_data
     * @return User|null
     */
    public function updateUserByID(string $uid, array $user_data = null)
    {
        // Check if user exists
        $firebase_user = $this->firebase->getUserByID($uid);

        // If user exists then create and return
        if ($firebase_user) {
            return $this->upsertUser($firebase_user, $user_data);
        }
    }

    /**
     * Create a new Firebase user with email & password, and upsert on Laravel
     *
     * @param  string  $email
     * @param  string  $password
     * @param  array|null  $user_data
     * @return User|null
     */
    public function createUserWithPassword(string $email, string $password, $user_data = null)
    {
        $firebase_user = $this->firebase->createUserWithEmailAndPassword($email, $password);

        if ($firebase_user) {
            $displayName = isset($user_data['name']) ?
                $user_data['name'] : (
                    isset($user_data['first_name']) && isset($user_data['last_name']) ?
                    $user_data['first_name'].' '.$user_data['last_name'] : null
                );
            $photoUrl = isset($user_data['picture']) ? $user_data['picture'] : null;
            $phoneNumber = isset($user_data['phone']) ? $user_data['phone'] : null;

            $firebase_user = $this->updateFirebaseUser($firebase_user, $displayName, $photoUrl, $phoneNumber);

            return $this->upsertUser($firebase_user, $user_data);
        }
    }

    /**
     * Update meta on a firebase user
     *
     * @param  UserRecord  $firebase_user
     * @param  string|null  $displayName
     * @param  string|null  $photoUrl
     * @param  string|null  $phoneNumber
     * @return UserRecord
     */
    public function updateFirebaseUser($firebase_user, $displayName = null, $photoUrl = null, $phoneNumber = null): UserRecord
    {
        $properties = [];
        if ($displayName) {
            $properties['displayName'] = $displayName;
        }
        if ($photoUrl) {
            $properties['photoUrl'] = $photoUrl;
        }
        if ($phoneNumber) {
            $properties['phoneNumber'] = $phoneNumber;
        }

        if (! empty($properties)) {
            return $this->firebase->updateUser($firebase_user->uid, $properties);
        }

        return $firebase_user;
    }

    /**
     * Authenticate a user with a decoded ID Token
     *
     * @param  object  $jwt
     * @return int user ID
     */
    public function getUserIDByAuthToken($token): int
    {

        // validate redirect & sign up
        $firebase_user = $this->firebase->getUserFromAuthToken($token);

        $user = $this->upsertUser($firebase_user);

        return $user->id;
    }
}
