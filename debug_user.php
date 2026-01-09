<?php

use App\Models\User;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::latest()->first();

if ($user) {
    echo "Last User ID: " . $user->id . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Must Change Password (Raw): " . $user->getAttributes()['must_change_password'] . "\n";
    echo "Must Change Password (Cast): " . ($user->must_change_password ? 'TRUE' : 'FALSE') . "\n";
    echo "Fillable: " . implode(', ', $user->getFillable()) . "\n";
} else {
    echo "No users found.\n";
}
