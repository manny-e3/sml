<?php

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request as FacadeRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Manually boot/setup
$app->boot();

echo "Starting Security Check (DB Mode)...\n";

$email = 'security_test@example.com';
$password = 'CorrectPassword123!';
$ip = '127.0.0.1';

// Create/Reset User
$user = User::firstOrCreate(['email' => $email], [
    'password' => Hash::make($password),
    'first_name' => 'Security',
    'last_name' => 'Test',
    'is_active' => true,
]);
$user->failed_logins = 0;
$user->lockout_time = null;
$user->save();

echo "User reset. Failed Logins: " . $user->failed_logins . "\n";

// Clear Logs
\App\Models\LoginLog::where('email', $email)->delete();

// Create Controller
$controller = $app->make(AuthController::class);

// Create Mock Request
function makeLoginRequest($email, $password, $ip) {
    $request = Request::create('/api/login', 'POST', [
        'email' => $email,
        'password' => $password
    ]);
    $request->server->set('REMOTE_ADDR', $ip);
    return $request;
}

// 1. Fail
echo "Attempt 1 (Fail)...\n";
$res1 = $controller->login(makeLoginRequest($email, 'Wrong', $ip));
$user->refresh();
echo "Status: " . $res1->getStatusCode() . ", Failed: " . $user->failed_logins . "\n";

// 2. Fail
echo "Attempt 2 (Fail)...\n";
$res2 = $controller->login(makeLoginRequest($email, 'Wrong', $ip));
$user->refresh();
echo "Status: " . $res2->getStatusCode() . ", Failed: " . $user->failed_logins . "\n";

// 3. Fail (Should Lockout depending on logic order)
// Code logic: Check limit FIRST? No.
// Update logic: Increment -> Check >= Limit.
// Default Limit is 3.
// 2 -> Increment to 3 -> Check >= 3 -> Lockout -> Return 429.
// OR: 1->1, 2->2, 3->Lockout?
// Let's see results.
echo "Attempt 3 (Fail)...\n";
$res3 = $controller->login(makeLoginRequest($email, 'Wrong', $ip));
$user->refresh();
echo "Status: " . $res3->getStatusCode() . ", Failed: " . $user->failed_logins . "\n";

if ($user->lockout_time) {
    echo "SUCCESS: Account Locked Out at " . $user->lockout_time . "\n";
} else {
    echo "FAILURE: Not Locked Out.\n";
}

// 4. Verify Logs
$logCount = \App\Models\LoginLog::where('email', $email)->count();
echo "Log Count: " . $logCount . "\n";

echo "\n--- Password History Check ---\n";
// Set password to 'Pass1'
$app->make(\App\Services\UserService::class)->updatePassword($user, 'Pass1');
echo "Password set to Pass1.\n";

// Set to 'Pass2'
$app->make(\App\Services\UserService::class)->updatePassword($user, 'Pass2');
echo "Password set to Pass2.\n";

// Try setting 'Pass1' again (Should Fail)
try {
    $app->make(\App\Services\UserService::class)->updatePassword($user, 'Pass1');
    echo "FAILURE: Reused Password 'Pass1' was accepted!\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "SUCCESS: Reused Password 'Pass1' was rejected!\n";
    print_r($e->errors());
}
