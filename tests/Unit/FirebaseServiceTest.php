<?php

use Salt\Firebase\Services\FirebaseService;

test('Trying to get a non-existent firebase user with email returns null', function () {
    mockFirebase();
    $firebase = app()->make(FirebaseService::class);
    expect($firebase->getUserByEmail('guest@example.com'))->toBeNull();
});

test('Trying to get a non-existent firebase user with an id returns null', function () {
    mockFirebase();
    $firebase = app()->make(FirebaseService::class);
    expect($firebase->getUserByID('guest'))->toBeNull();
});
