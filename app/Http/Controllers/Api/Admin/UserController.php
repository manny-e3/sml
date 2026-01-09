<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PendingUser;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * The user service instance.
     *
     * @var UserService
     */
    protected $userService;

    /**
     * Create a new controller instance.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get paginated list of users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $users = $this->userService->getAllUsers($perPage);

        return response()->json($users, 200);
    }

    /**
     * Create a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'department' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'exists:roles,name'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        try {
            // Check if user is super_admin to auto-approve
            $autoApprove = $request->user()->hasRole('super_admin');
            
            $user = $this->userService->createUser($validated, $autoApprove);

            return response()->json([
                'message' => $autoApprove 
                    ? 'User created and approved successfully.' 
                    : 'User created successfully. Pending approval.',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create user.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get a single user.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        $user = $this->userService->getUserById($user);

        return response()->json($user, 200);
    }

    /**
     * Update a user.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'department' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'exists:roles,name'],
        ]);

        try {
            $updatedUser = $this->userService->updateUser($user, $validated);

            return response()->json([
                'message' => 'User updated successfully.',
                'user' => $updatedUser,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update user.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user, auth()->id());

            return response()->json([
                'message' => 'User deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Get paginated list of pending users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $users = $this->userService->getPendingUsers($perPage);

        return response()->json($users, 200);
    }

    /**
     * Approve a pending user.
     *
     * @param Request $request
     * @param PendingUser $pendingUser
     * @return JsonResponse
     */
    public function approve(Request $request, PendingUser $pendingUser): JsonResponse
    {
        try {
            $approvedUser = $this->userService->approveUser($pendingUser, auth()->id());

            return response()->json([
                'message' => 'User approved successfully.',
                'user' => $approvedUser,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject a pending user.
     *
     * @param Request $request
     * @param PendingUser $pendingUser
     * @return JsonResponse
     */
    public function reject(Request $request, PendingUser $pendingUser): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $rejectedUser = $this->userService->rejectUser(
                $pendingUser, 
                auth()->id(), 
                $validated['reason']
            );

            return response()->json([
                'message' => 'User rejected successfully.',
                'user' => $rejectedUser,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
