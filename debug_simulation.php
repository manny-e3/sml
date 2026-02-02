<?php

use App\Models\User;
use App\Models\PendingUser;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Fake auth
$superAdmin = User::factory()->create();
auth()->login($superAdmin);

Illuminate\Support\Facades\Mail::fake();

$service = new UserService();

echo "Creating Pending User...\n";
$pending = PendingUser::create([
    'firstname' => 'Test',
    'last_name' => 'Pending',
    'email' => 'test.pending.' . time() . '@example.com',
    'role' => 'inputter',
    'requested_by' => $superAdmin->id,
    'approval_status' => 'pending',
    'is_active' => true,
]);

echo "Approving User...\n";
// Create another user as approver to avoid self-approval check
$approver = User::factory()->create();

try {
    $approvedUser = $service->approveUser($pending, $approver->id);
    
    echo "Approved User ID: " . $approvedUser->id . "\n";
    echo "Must Change Password: " . ($approvedUser->must_change_password ? 'TRUE' : 'FALSE') . "\n";
    
    // Check raw DB
    $raw = \Illuminate\Support\Facades\DB::table('users')->where('id', $approvedUser->id)->first();
    echo "Raw DB Value: " . $raw->must_change_password . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
