<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
if ($user) {
    try {
        $token = $user->createToken('test')->plainTextToken;
        echo "SUCCESS: " . $token;
    } catch (\Throwable $e) {
        echo "ERROR: " . $e->getMessage();
    }
} else {
    echo "No user found.";
}
