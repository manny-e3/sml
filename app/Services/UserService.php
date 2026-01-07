<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\PendingUserCreated;
use App\Mail\UserApproved;
use App\Mail\UserRejected;

class UserService
{
    /**
     * Get paginated list of users with their roles.
     *
     * @param int $perPage
     * @param string|null $approvalStatus Filter by approval status (null = approved only for backward compatibility)
     * @return LengthAwarePaginator
     */
    public function getAllUsers(int $perPage = 15, ?string $approvalStatus = User::STATUS_APPROVED): LengthAwarePaginator
    {
        $query = User::with('roles');
        
        // Filter by approval status if specified
        if ($approvalStatus !== null) {
            $query->where('approval_status', $approvalStatus);
        }
        
        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a new user with role assignment.
     *
     * @param array $data
     * @param bool $autoApprove Whether to auto-approve the user (for super_admin)
     * @return User
     * @throws \Exception
     */
    public function createUser(array $data, bool $autoApprove = false): User
    {
        DB::beginTransaction();

        try {
            // Prepare user data
            $userData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'department' => $data['department'] ?? null,
                'password' => Hash::make($data['password']),
            ];
            
            // Set approval status
            if ($autoApprove) {
                $userData['approval_status'] = User::STATUS_APPROVED;
                $userData['approved_at'] = now();
                // Only set approved_by if there's an authenticated user
                if (auth()->check()) {
                    $userData['approved_by'] = auth()->id();
                }
            } else {
                $userData['approval_status'] = User::STATUS_PENDING;
            }
            
            // Create user
            $user = User::create($userData);

            // Assign role
            $user->assignRole($data['role']);

            DB::commit();

            // Send notification to authorisers if user is pending
            if (!$autoApprove) {
                $this->notifyAuthorisers($user);
            } else {
                // If auto-approved, send welcome email with credentials
                // Generate a temporary password for auto-approved users
                $tempPassword = $this->generateSecurePassword();
                $user->password = Hash::make($tempPassword);
                $user->save();
                
                Mail::to($user->email)->send(new UserApproved($user, $tempPassword));
            }

            // Reload user with roles
            return $user->load('roles');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Get a single user with roles.
     *
     * @param User $user
     * @return User
     */
    public function getUserById(User $user): User
    {
        return $user->load('roles');
    }

    /**
     * Update user information and role.
     *
     * @param User $user
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function updateUser(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            // Update basic information
            $user->first_name = $data['first_name'];
            $user->last_name = $data['last_name'];
            $user->email = $data['email'];
            $user->department = $data['department'] ?? null;

            // Update password if provided
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            // Sync roles
            $user->syncRoles([$data['role']]);

            DB::commit();

            // Reload user with roles
            return $user->load('roles');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @param int|null $currentUserId
     * @return bool
     * @throws \Exception
     */
    public function deleteUser(User $user, ?int $currentUserId = null): bool
    {
        // Prevent self-deletion
        if ($currentUserId && $user->id === $currentUserId) {
            throw new \Exception('You cannot delete yourself.');
        }

        try {
            $user->delete();
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Check if user can be deleted.
     *
     * @param User $user
     * @param int|null $currentUserId
     * @return bool
     */
    public function canDeleteUser(User $user, ?int $currentUserId = null): bool
    {
        return !($currentUserId && $user->id === $currentUserId);
    }

    /**
     * Get users by role.
     *
     * @param string $roleName
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUsersByRole(string $roleName, int $perPage = 15): LengthAwarePaginator
    {
        return User::role($roleName)
            ->with('roles')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Search users by name or email.
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchUsers(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return User::where(function ($q) use ($query) {
            $q->where('first_name', 'like', "%{$query}%")
              ->orWhere('last_name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%");
        })
        ->with('roles')
        ->latest()
        ->paginate($perPage);
    }

    /**
     * Update user password.
     *
     * @param User $user
     * @param string $password
     * @return User
     */
    public function updatePassword(User $user, string $password): User
    {
        $user->password = Hash::make($password);
        $user->save();

        return $user;
    }

    /**
     * Suspend or activate user.
     *
     * @param User $user
     * @param bool $suspend
     * @return User
     */
    public function toggleUserStatus(User $user, bool $suspend = true): User
    {
        $user->is_active = !$suspend; // Invert because is_active is opposite of suspended
        $user->save();

        return $user;
    }

    /**
     * Get paginated list of pending users.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPendingUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::pending()
            ->with(['roles', 'approvedBy'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Approve a pending user.
     *
     * @param User $user
     * @param int $approverId
     * @return User
     * @throws \Exception
     */
    public function approveUser(User $user, int $approverId): User
    {
        // Check if user is pending
        if (!$user->isPending()) {
            throw new \Exception('Only pending users can be approved.');
        }

        // Prevent self-approval
        if (!$this->canUserApprove($approverId, $user->id)) {
            throw new \Exception('You cannot approve a user you created.');
        }

        DB::beginTransaction();

        try {
            // Generate secure password
            $password = $this->generateSecurePassword();
            
            $user->approval_status = User::STATUS_APPROVED;
            $user->approved_by = $approverId;
            $user->approved_at = now();
            $user->rejection_reason = null; // Clear any previous rejection reason
            $user->password = Hash::make($password); // Set the generated password
            $user->save();

            DB::commit();

            // Send welcome email with credentials
            Mail::to($user->email)->send(new UserApproved($user, $password));

            return $user->load(['roles', 'approvedBy']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to approve user: ' . $e->getMessage());
        }
    }

    /**
     * Reject a pending user.
     *
     * @param User $user
     * @param int $approverId
     * @param string $reason
     * @return User
     * @throws \Exception
     */
    public function rejectUser(User $user, int $approverId, string $reason): User
    {
        // Check if user is pending
        if (!$user->isPending()) {
            throw new \Exception('Only pending users can be rejected.');
        }

        // Prevent self-rejection
        if (!$this->canUserApprove($approverId, $user->id)) {
            throw new \Exception('You cannot reject a user you created.');
        }

        DB::beginTransaction();

        try {
            $user->approval_status = User::STATUS_REJECTED;
            $user->approved_by = $approverId;
            $user->approved_at = now();
            $user->rejection_reason = $reason;
            $user->save();

            DB::commit();

            // Send rejection notification
            Mail::to($user->email)->send(new UserRejected($user, $reason));

            return $user->load(['roles', 'approvedBy']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to reject user: ' . $e->getMessage());
        }
    }

    /**
     * Check if a user can approve another user.
     * Prevents self-approval (approving a user you created).
     *
     * @param int $approverId
     * @param int $userToApproveId
     * @return bool
     */
    public function canUserApprove(int $approverId, int $userToApproveId): bool
    {
        // For now, we'll allow approval as long as it's not the same user
        // In a more complex system, you might check who created the user
        return $approverId !== $userToApproveId;
    }

    /**
     * Generate a secure random password.
     *
     * @return string
     */
    private function generateSecurePassword(): string
    {
        // Generate a 12-character password with uppercase, lowercase, numbers, and special characters
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 0; $i < 8; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password
        return str_shuffle($password);
    }

    /**
     * Notify all authorisers about a pending user.
     *
     * @param User $user
     * @return void
     */
    private function notifyAuthorisers(User $user): void
    {
        // Get all users with authoriser or super_admin role
        $authorisers = User::role(['authoriser', 'super_admin'])
            ->where('approval_status', User::STATUS_APPROVED)
            ->where('is_active', true)
            ->get();
        
        foreach ($authorisers as $authoriser) {
            Mail::to($authoriser->email)->send(new PendingUserCreated($user));
        }
    }
}
