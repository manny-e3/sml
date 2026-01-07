<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\UserService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService();

        // Create roles for testing
        Role::create(['name' => 'inputter']);
        Role::create(['name' => 'authoriser']);
        Role::create(['name' => 'super_admin']);
    }

    /** @test */
    public function it_can_get_all_users_paginated()
    {
        // Arrange
        User::factory()->count(20)->create();

        // Act
        $result = $this->userService->getAllUsers(10);

        // Assert
        $this->assertEquals(10, $result->count());
        $this->assertEquals(20, $result->total());
    }

    /** @test */
    public function it_can_create_user_with_role()
    {
        // Arrange
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'department' => 'IT',
            'password' => 'Password123!',
            'role' => 'inputter',
        ];

        // Act - Create without auto-approve (pending)
        $user = $this->userService->createUser($userData, false);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John', $user->first_name);
        $this->assertEquals('Doe', $user->last_name);
        $this->assertEquals('john.doe@example.com', $user->email);
        $this->assertEquals('IT', $user->department);
        $this->assertTrue($user->hasRole('inputter'));
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertEquals(User::STATUS_PENDING, $user->approval_status);
        $this->assertNull($user->approved_by);
        $this->assertNull($user->approved_at);
    }

    /** @test */
    public function it_can_get_user_by_id()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('inputter');

        // Act
        $result = $this->userService->getUserById($user);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertTrue($result->relationLoaded('roles'));
    }

    /** @test */
    public function it_can_update_user_information()
    {
        // Arrange
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);
        $user->assignRole('inputter');

        $updateData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'department' => 'Finance',
            'role' => 'authoriser',
        ];

        // Act
        $updatedUser = $this->userService->updateUser($user, $updateData);

        // Assert
        $this->assertEquals('Jane', $updatedUser->first_name);
        $this->assertEquals('Smith', $updatedUser->last_name);
        $this->assertEquals('jane@example.com', $updatedUser->email);
        $this->assertEquals('Finance', $updatedUser->department);
        $this->assertTrue($updatedUser->hasRole('authoriser'));
        $this->assertFalse($updatedUser->hasRole('inputter'));
    }

    /** @test */
    public function it_can_update_user_password()
    {
        // Arrange
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);
        $user->assignRole('inputter');

        $updateData = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'department' => $user->department,
            'role' => 'inputter',
            'password' => 'NewPassword123!',
        ];

        // Act
        $updatedUser = $this->userService->updateUser($user, $updateData);

        // Assert
        $this->assertTrue(Hash::check('NewPassword123!', $updatedUser->password));
        $this->assertFalse(Hash::check('OldPassword123!', $updatedUser->password));
    }

    /** @test */
    public function it_can_delete_user()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->userService->deleteUser($user);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function it_prevents_self_deletion()
    {
        // Arrange
        $user = User::factory()->create();

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You cannot delete yourself.');

        // Act
        $this->userService->deleteUser($user, $user->id);
    }

    /** @test */
    public function it_can_check_if_user_can_be_deleted()
    {
        // Arrange
        $user = User::factory()->create();
        $currentUser = User::factory()->create();

        // Act & Assert
        $this->assertTrue($this->userService->canDeleteUser($user, $currentUser->id));
        $this->assertFalse($this->userService->canDeleteUser($user, $user->id));
    }

    /** @test */
    public function it_can_get_users_by_role()
    {
        // Arrange
        $inputters = User::factory()->count(5)->create();
        foreach ($inputters as $user) {
            $user->assignRole('inputter');
        }

        $authorisers = User::factory()->count(3)->create();
        foreach ($authorisers as $user) {
            $user->assignRole('authoriser');
        }

        // Act
        $result = $this->userService->getUsersByRole('inputter', 10);

        // Assert
        $this->assertEquals(5, $result->count());
    }

    /** @test */
    public function it_can_search_users_by_name()
    {
        // Arrange
        User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
        ]);

        User::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'email' => 'bob@example.com',
        ]);

        // Act
        $result = $this->userService->searchUsers('John', 10);

        // Assert
        $this->assertEquals(2, $result->count()); // John Doe and Bob Johnson
    }

    /** @test */
    public function it_can_search_users_by_email()
    {
        // Arrange
        User::factory()->create(['email' => 'test@example.com']);
        User::factory()->create(['email' => 'another@example.com']);
        User::factory()->create(['email' => 'different@test.com']);

        // Act
        $result = $this->userService->searchUsers('example', 10);

        // Assert
        $this->assertEquals(2, $result->count());
    }

    /** @test */
    public function it_can_update_user_password_directly()
    {
        // Arrange
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        // Act
        $updatedUser = $this->userService->updatePassword($user, 'NewPassword123!');

        // Assert
        $this->assertTrue(Hash::check('NewPassword123!', $updatedUser->password));
    }

    /** @test */
    public function it_can_suspend_user()
    {
        // Arrange
        $user = User::factory()->create(['is_active' => true]);

        // Act
        $suspendedUser = $this->userService->toggleUserStatus($user, true);

        // Assert
        $this->assertFalse($suspendedUser->is_active); // Suspended means not active
    }

    /** @test */
    public function it_can_activate_user()
    {
        // Arrange
        $user = User::factory()->create(['is_active' => false]);

        // Act
        $activatedUser = $this->userService->toggleUserStatus($user, false);

        // Assert
        $this->assertTrue($activatedUser->is_active); // Not suspended means active
    }

    /** @test */
    public function it_rolls_back_transaction_on_create_failure()
    {
        // Arrange
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'role' => 'non_existent_role', // This should cause failure
        ];

        // Assert
        $this->expectException(\Exception::class);

        // Act
        $this->userService->createUser($userData);

        // Verify no user was created
        $this->assertDatabaseMissing('users', ['email' => 'john@example.com']);
    }

    /** @test */
    public function it_creates_approved_user_when_auto_approve_is_true()
    {
        // Arrange
        $admin = User::factory()->create(['approval_status' => User::STATUS_APPROVED]);
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $userData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'department' => 'Finance',
            'password' => 'Password123!',
            'role' => 'authoriser',
        ];

        // Act - Create with auto-approve
        $user = $this->userService->createUser($userData, true);

        // Assert
        $this->assertEquals(User::STATUS_APPROVED, $user->approval_status);
        $this->assertEquals($admin->id, $user->approved_by);
        $this->assertNotNull($user->approved_at);
    }

    /** @test */
    public function it_can_get_pending_users()
    {
        // Arrange
        User::factory()->count(3)->create(['approval_status' => User::STATUS_PENDING]);
        User::factory()->count(2)->create(['approval_status' => User::STATUS_APPROVED]);

        // Act
        $result = $this->userService->getPendingUsers(10);

        // Assert
        $this->assertEquals(3, $result->count());
    }

    /** @test */
    public function it_can_approve_pending_user()
    {
        // Arrange
        $approver = User::factory()->create(['approval_status' => User::STATUS_APPROVED]);
        $approver->assignRole('authoriser');
        
        $pendingUser = User::factory()->create(['approval_status' => User::STATUS_PENDING]);
        $pendingUser->assignRole('inputter');

        // Act
        $approvedUser = $this->userService->approveUser($pendingUser, $approver->id);

        // Assert
        $this->assertEquals(User::STATUS_APPROVED, $approvedUser->approval_status);
        $this->assertEquals($approver->id, $approvedUser->approved_by);
        $this->assertNotNull($approvedUser->approved_at);
        $this->assertNull($approvedUser->rejection_reason);
    }

    /** @test */
    public function it_can_reject_pending_user()
    {
        // Arrange
        $approver = User::factory()->create(['approval_status' => User::STATUS_APPROVED]);
        $approver->assignRole('authoriser');
        
        $pendingUser = User::factory()->create(['approval_status' => User::STATUS_PENDING]);
        $pendingUser->assignRole('inputter');

        // Act
        $rejectedUser = $this->userService->rejectUser($pendingUser, $approver->id, 'Invalid credentials');

        // Assert
        $this->assertEquals(User::STATUS_REJECTED, $rejectedUser->approval_status);
        $this->assertEquals($approver->id, $rejectedUser->approved_by);
        $this->assertNotNull($rejectedUser->approved_at);
        $this->assertEquals('Invalid credentials', $rejectedUser->rejection_reason);
    }

    /** @test */
    public function it_throws_exception_when_approving_non_pending_user()
    {
        // Arrange
        $approver = User::factory()->create(['approval_status' => User::STATUS_APPROVED]);
        $approvedUser = User::factory()->create(['approval_status' => User::STATUS_APPROVED]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only pending users can be approved.');

        // Act
        $this->userService->approveUser($approvedUser, $approver->id);
    }

    /** @test */
    public function it_throws_exception_when_rejecting_non_pending_user()
    {
        // Arrange
        $approver = User::factory()->create(['approval_status' => User::STATUS_APPROVED]);
        $approvedUser = User::factory()->create(['approval_status' => User::STATUS_APPROVED]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only pending users can be rejected.');

        // Act
        $this->userService->rejectUser($approvedUser, $approver->id, 'Test reason');
    }

    /** @test */
    public function it_prevents_self_approval()
    {
        // Arrange
        $user = User::factory()->create(['approval_status' => User::STATUS_PENDING]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You cannot approve a user you created.');

        // Act
        $this->userService->approveUser($user, $user->id);
    }

    /** @test */
    public function it_only_returns_approved_users_by_default()
    {
        // Arrange
        User::factory()->count(5)->create(['approval_status' => User::STATUS_APPROVED]);
        User::factory()->count(3)->create(['approval_status' => User::STATUS_PENDING]);
        User::factory()->count(2)->create(['approval_status' => User::STATUS_REJECTED]);

        // Act
        $result = $this->userService->getAllUsers(20);

        // Assert
        $this->assertEquals(5, $result->count());
    }
}
