<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Kreait\Firebase\Auth\UserRecord;
use Kreait\Firebase\Contract;
use Kreait\Firebase\Exception\Auth\FailedToVerifySessionCookie;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use Salt\Firebase\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('');

/**
 * Mocks internal methods on Firebase SDK
 * so that tests don't need to touch APIs
 * directly.
 *
 * email: 'guest@example.com' returns null
 * localId/uid: 'guest' returns null
 * firebase_id_token: 'VALID' is a valid session
 *
 * @param $user The mocked firebase user
 */
function mockFirebase($user = [
    'localId' => '123',
    'email' => 'user@example.com',
])
{
    $jwt = mockToken($user);
    $auth = mockAuth($jwt, $user);
    app()->bind(Contract\Auth::class, fn () => $auth);

    return [$auth, $jwt];
}

/**
 * Mock the jwt
 */
function mockToken($user)
{
    $mock = mock(UnencryptedToken::class);

    $mock->shouldReceive('claims')
        ->zeroOrMoreTimes()
        ->andReturn(new DataSet($user, ''));

    return $mock;
}

/**
 * Mock the auth methods
 */
function mockAuth($jwt, $user)
{
    $mock = mock(AuthContract::class);

    $user['lastLoginAt'] = Carbon::now();
    $user['createdAt'] = Carbon::now();

    $mock->shouldReceive('verifyIdToken')
        ->zeroOrMoreTimes()
        ->andReturnUsing(fn ($token) => $token === 'VALID' ? $jwt : throw new FailedToVerifyToken());
    $mock->shouldReceive('getUser')
        ->zeroOrMoreTimes()
        ->andReturnUsing(fn ($uid) => $uid !== 'guest' ? UserRecord::fromResponseData($user) : throw new \Exception('Not found'));
    $mock->shouldReceive('updateUser')
        ->zeroOrMoreTimes()
        ->andReturnUsing(fn ($id, $data) => UserRecord::fromResponseData(array_merge($user, $data)));

    $mock->shouldReceive('getUserByEmail')
        ->zeroOrMoreTimes()
        ->andReturnUsing(fn ($email) => $email !== 'guest@example.com' ? UserRecord::fromResponseData($user) : throw new \Exception('Not found'));
    $mock->shouldReceive('createSessionCookie')
        ->zeroOrMoreTimes()
        ->andReturnUsing(fn ($token) => $token);

    $mock->shouldReceive('verifySessionCookie')
        ->zeroOrMoreTimes()
        ->andReturnUsing(fn ($token) => $token === 'VALID' ? true : throw new FailedToVerifySessionCookie($token));
    $mock->shouldReceive('createUserWithEmailAndPassword')
        ->zeroOrMoreTimes()
        ->andReturn(UserRecord::fromResponseData($user));

    return $mock;
}

/**
 * Set the currently logged in user for the application.
 *
 * @return TestCase
 */
function actingAs(Authenticatable $user, $session = ['firebase_id_token' => 'VALID'])
{
    return test()->actingAs($user)->withSession($session);
}
