<?php

use Illuminate\Http\Response;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use Salt\Firebase\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(
        [
            'uid' => '123',
            'email' => 'user@example.com',
        ]
    );
});

test('User cannot login without sending a token', function () {
    mockFirebase([
        'localId' => '000',
        'email' => 'anonymous@example.com',
    ]);

    get(route('login.sso.verify', []))
        ->assertRedirect(route('error'));
});

test('User can attempt login with a valid token but requires an account in the database', function () {
    mockFirebase([
        'localId' => '000',
        'email' => 'anonymous@example.com',
    ]);

    get(route('login.sso.verify', ['token' => 'VALID']))
        ->assertRedirect(route('error'));
});

test('User can have a valid account but cannot login with an invalid token', function () {
    mockFirebase([
        'localId' => '123',
        'email' => 'user@example.com',
    ]);

    get(route('login.sso.verify', ['token' => 'INVALID']))
        ->assertRedirect(route('error'));
});

test('User can login if they already have an account with matching uid', function () {
    mockFirebase([
        'localId' => '123',
        'email' => 'user@example.com',
    ]);

    get(route('login.sso.verify', ['token' => 'VALID']))
        ->assertRedirect(route('index'));
});

test('User can signup if they have an email matching the allow list', function () {
    mockFirebase([
        'localId' => '111',
        'email' => 'new@okayemail.com',
    ]);

    get(route('login.sso.verify', ['token' => 'VALID', 'reg' => true]))
        ->assertRedirect(route('index'));

    assertDatabaseHas('users', ['uid' => '111', 'email' => 'new@okayemail.com']);
});

test('User cannot signup if email does match the allow list', function () {
    mockFirebase([
        'localId' => '222',
        'email' => 'new@example.com',
    ]);

    get(route('login.sso.verify', ['token' => 'VALID', 'reg' => true]))
        ->assertRedirect(route('error'));

    assertDatabaseMissing('users', ['uid' => '222', 'email' => 'new@example.com']);
});

test('User cannot signup if their token is not valid', function () {
    mockFirebase([
        'localId' => '333',
        'email' => 'new@okayemail.com',
    ]);

    get(route('login.sso.verify', ['token' => 'INVALID', 'reg' => true]))
        ->assertRedirect(route('error'));

    assertDatabaseMissing('users', ['uid' => '333', 'email' => 'new@okayemail.com']);
});

test('User can access the route index if logged in', function () {
    mockFirebase();
    actingAs($this->user)
        ->get(route('index'))
        ->assertStatus(200);
});

test('User cannot access login if they are already logged in', function () {
    actingAs($this->user)
        ->get(route('login.sso.verify'))
        ->assertRedirect(route('index'));
});

test('User can log out if they are already logged in', function () {
    actingAs($this->user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));
});

test('Error message is shown on error route', function () {
    get(route('error'))->assertStatus(Response::HTTP_OK);
});
