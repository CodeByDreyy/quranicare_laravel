<?php

use App\Models\User;

// Create test user
$user = User::firstOrCreate([
    'email' => 'test@sakinah.com'
], [
    'name' => 'Test User Sakinah',
    'password' => bcrypt('password')
]);

// Create token
$token = $user->createToken('sakinah-test')->plainTextToken;

echo "User created: {$user->email}\n";
echo "Token: {$token}\n";