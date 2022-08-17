<?php

use function Pest\Laravel\assertDatabaseHas;
use Salt\Firebase\Models\User;
use Salt\Firebase\Repositories\CustomUserRepository;

beforeEach(function () {
    $this->user = User::factory()->create(
        [
            'uid' => '123',
            'email' => 'user@example.com',
        ]
    );
});

test('Creates an existing Firebase user on the database', function () {
    mockFirebase([
        'localId' => '12345',
        'email' => 'new@example.com',
    ]);

    app()->make(CustomUserRepository::class)->upsertOrCreateUserByEmail('new@example.com');

    assertDatabaseHas('users', ['uid' => '12345', 'email' => 'new@example.com']);
});

test('Creates a new Firebase user and creates it on the database', function () {
    mockFirebase([
        'localId' => '12345',
        'email' => 'guest@example.com',
    ]);

    app()->make(CustomUserRepository::class)->upsertOrCreateUserByEmail('guest@example.com');

    assertDatabaseHas('users', ['uid' => '12345', 'email' => 'guest@example.com']);

    mockFirebase([
        'localId' => '12345',
        'email' => 'guest@example.com',
    ]);

    app()->make(CustomUserRepository::class)->upsertOrCreateUserByEmail('guest@example.com', [
        'name' => 'Guest User',
    ]);

    assertDatabaseHas('users', ['uid' => '12345', 'email' => 'guest@example.com']);
});

test('Updates a firebase user by id', function () {
    mockFirebase([
        'localId' => '12345',
        'email' => 'user2@example.com',
    ]);

    User::factory()->create([
        'uid' => '12345',
        'id' => '2',
        'email' => 'user2@example.com',
    ]);

    app()->make(CustomUserRepository::class)->updateUserByID('12345', ['name' => 'Updated name']);

    assertDatabaseHas('users', ['uid' => '12345', 'name' => 'Updated name']);
});

test('Can get a user by auth token and copy them to the database', function () {
    mockFirebase([
        'localId' => '12345',
        'email' => 'user2@example.com',
    ]);

    app()->make(CustomUserRepository::class)->getUserIDByAuthToken('VALID');

    assertDatabaseHas('users', ['uid' => '12345', 'email' => 'user2@example.com']);
});
