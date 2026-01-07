<?php

namespace App\Services;

use App\Models\User;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Authenticate user and generate API token.
     *
     * @param array $credentials
     * @return array|null
     */
    public function login(array $credentials): ?array
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Revoke the user's current access token.
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool
    {
        $user->currentAccessToken()->delete();
        return true;
    }

    /**
     * Send password reset link to user's email.
     *
     * @param string $email
     * @return array
     * @throws \Exception
     */
    public function sendPasswordResetLink(string $email): array
    {
        $user = User::where('email', $email)->first();

        // For security, don't reveal if user exists
        if (!$user) {
            return [
                'success' => true,
                'message' => 'If an account exists with this email, you will receive a password reset link.',
            ];
        }

        // Generate password reset token
        $token = Password::createToken($user);

        // Send password reset email
        try {
            Mail::to($user->email)->send(new ResetPasswordMail($user, $token));

            return [
                'success' => true,
                'message' => 'Password reset link has been sent to your email.',
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to send password reset email: ' . $e->getMessage());
        }
    }

    /**
     * Reset user's password using token.
     *
     * @param array $data
     * @return array
     */
    public function resetPassword(array $data): array
    {
        $status = Password::reset(
            $data,
            function ($user, $password) {
                $this->updatePassword($user, $password);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return [
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ];
        }

        return [
            'success' => false,
            'message' => __($status),
        ];
    }

    /**
     * Verify if password reset token is valid.
     *
     * @param string $email
     * @param string $token
     * @return array
     */
    public function verifyResetToken(string $email, string $token): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'valid' => false,
                'message' => 'Invalid token or email.',
            ];
        }

        // Check if token is valid
        if (!Password::tokenExists($user, $token)) {
            return [
                'valid' => false,
                'message' => 'Invalid or expired token.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Token is valid.',
        ];
    }

    /**
     * Update user's password.
     *
     * @param User $user
     * @param string $password
     * @return void
     */
    protected function updatePassword(User $user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password)
        ])->setRememberToken(Str::random(60));

        $user->save();
    }

    /**
     * Get authenticated user data.
     *
     * @param User $user
     * @return User
     */
    public function getAuthenticatedUser(User $user): User
    {
        return $user;
    }
}
