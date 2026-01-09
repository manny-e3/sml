<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Models\User;
use App\Models\LoginLog;
use App\Models\LoginCount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter; // Keep for now in case
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    /**
     * The authentication service instance.
     *
     * @var AuthService
     */
    protected $authService;

    /**
     * Create a new controller instance.
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param Request $request
     * @return JsonResponse
     */
    /**
     * @OA\Post(
     *      path="/api/login",
     *      operationId="login",
     *      tags={"Authentication"},
     *      summary="User Login",
     *      description="Authenticate user and return API token",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="Password123!")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="user", type="object"),
     *              @OA\Property(property="token", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=429,
     *          description="Too Many Attempts (Lockout)",
     *      ),
     *       @OA\Response(
     *          response=403,
     *          description="Forbidden (Active/Password Change)",
     *      )
     * )
     */
    #[OA\Post(
        path: "/api/login",
        operationId: "login",
        summary: "User Login",
        description: "Authenticate user and return API token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "Password123!")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "user", type: "object"),
                        new OA\Property(property: "token", type: "string")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 429, description: "Too Many Attempts (Lockout)"),
            new OA\Response(response: 403, description: "Forbidden (Active/Password Change)")
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $ipAddress = $request->ip();
        $user = User::where('email', $request->email)->first();

        // 1. User not found
        if (!$user) {
            $this->logLoginAttempt(null, 'failed', $ipAddress, 'Invalid email or password', $request->email);
            return response()->json([
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        // 2. Check Active Status
        if (!$user->is_active) {
            $this->logLoginAttempt($user->id, 'failed', $ipAddress, 'Account not active', $user->email);
            return response()->json([
                'message' => 'Your account is not active. Please contact the administrator.',
            ], 403);
        }

        // 3. Check Lockout
        if ($user->lockout_time) {
             // Permanent lockout logic per snippet
            $this->logLoginAttempt($user->id, 'failed', $ipAddress, 'Account locked', $user->email);
            return response()->json([
                'message' => 'Your account has been locked. Please reset your password.',
            ], 403);
        }

        // 4. Attempt Login via Service
        $result = $this->authService->login($credentials);

        if (!$result) {
            // Increment Failed Logins
            $user->increment('failed_logins'); // native increment

            // Check Limit
            $loginCount = LoginCount::orderby('id', 'DESC')->first();
            $limit = $loginCount ? $loginCount->login_count : 3; // Default 3

            if ($user->failed_logins >= $limit) {
                $user->lockout_time = now();
                $user->save();
                $this->logLoginAttempt($user->id, 'failed', $ipAddress, 'Account locked due to failed attempts', $user->email);
                return response()->json([
                    'message' => 'Your account has been locked. Please reset your password.',
                ], 429); // or 403
            }

            $this->logLoginAttempt($user->id, 'failed', $ipAddress, 'Incorrect email or password', $user->email);
            return response()->json([
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

       

        // Password Expiry
        $loginCount = LoginCount::orderby('id', 'DESC')->first();
        $ageLimit = $loginCount ? $loginCount->password_age : 30;
        
        if ($user->password_changed_at && now()->diffInDays($user->password_changed_at) >= $ageLimit) {
            // Require change
             return response()->json([
                'message' => 'You must change your password as it has been ' . $ageLimit . ' days since the last update.',
                'require_change_password' => true,
            ], 403);
        }

        // Reset counters
        $user->failed_logins = 0;
        $user->lockout_time = null;
        $user->save();
        Session::forget('disclaimer_accepted');

        $this->logLoginAttempt($user->id, 'success', $ipAddress, 'Login successful', $user->email);

        if (isset($result['require_change_password']) && $result['require_change_password']) {
            return response()->json([
                'message' => 'You must change your password before logging in.',
                'require_change_password' => true,
            ], 403);
        }

        return response()->json($result, 200);
    }

    // Log login attempts
    protected function logLoginAttempt($userId = null, $status, $ipAddress, $message = null, $email = null)
    {
        LoginLog::create([
            'user_id'    => $userId,
            'name'       => $userId ? User::find($userId)->getFullNameAttribute() : null, // Use accessor
            'email'      => $email, 
            'status'     => $status,
            'ip_address' => $ipAddress,
            'message'    => $message,
        ]);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/logout",
        operationId: "logout",
        summary: "User Logout",
        description: "Revoke the user's current access token",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Logged out successfully")
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Get the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser($request->user());

        return response()->json($user, 200);
    }

    /**
     * Handle forgot password request.
     *
     * @param Request $request
     * @return JsonResponse
     */

    #[OA\Post(
        path: "/api/forgot-password",
        operationId: "forgotPassword",
        summary: "Forgot Password",
        description: "Send password reset link to user email",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Reset link sent")
        ]
    )]
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $result = $this->authService->sendPasswordResetLink($request->email);

            return response()->json([
                'message' => $result['message'],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send password reset email. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Handle password reset.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/reset-password",
        operationId: "resetPassword",
        summary: "Reset Password",
        description: "Reset user password using token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["token", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "token", type: "string"),
                    new OA\Property(property: "email", type: "string", format: "email"),
                    new OA\Property(property: "password", type: "string", format: "password"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Password reset successfully"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',             
                'regex:/[a-z]/',      
                'regex:/[A-Z]/',      
                'regex:/[0-9]/',      
                'regex:/[@$!.%*#?&]/',
            ],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Check if the new password is in any of the last 10 used passwords (Configurable)
        $loginCount = LoginCount::orderby('id', 'DESC')->first();
        $historyLimit = $loginCount ? $loginCount->login_history_count : 10;
        
        $pastPasswords = $user->passwordHistories()->latest()->take($historyLimit)->pluck('password');
        
        foreach ($pastPasswords as $pastPassword) {
            if (Hash::check($request->password, $pastPassword)) {
                return response()->json([
                    'message' => 'You cannot reuse your last ' . $historyLimit . ' passwords.',
                    'errors' => ['password' => ['You cannot reuse your last ' . $historyLimit . ' passwords.']]
                ], 422);
            }
        }

        // Update the user's password
        $user->password = Hash::make($request->password);
        $user->password_changed_at = now();
        $user->must_change_password = false; // Ensure logic matches previous updatePassword
        
        // Reset lockout info
        $user->lockout_time  = null; 
        $user->failed_logins = 0;    
        $user->save();

        // Add the new password to the history
        $user->passwordHistories()->create(['password' => $user->password]);

        // Delete the password reset record
        // Delete the password reset record
        DB::table('password_reset_tokens')->where(['email' => $request->email])->delete();

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ], 200);
    }

    /**
     * Verify password reset token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyResetToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
        ]);

        $result = $this->authService->verifyResetToken(
            $request->email,
            $request->token
        );

        $statusCode = $result['valid'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }
    #[OA\Post(
        path: "/api/change-initial-password",
        operationId: "changeInitialPassword",
        summary: "Change Initial Password",
        description: "Change password for the first time (forced)",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "current_password", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email"),
                    new OA\Property(property: "current_password", type: "string", format: "password"),
                    new OA\Property(property: "password", type: "string", format: "password"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Password changed successfully"),
            new OA\Response(response: 400, description: "Invalid credentials or Validation Error")
        ]
    )]
    public function changeInitialPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'current_password' => ['required'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!.%*#?&]/',
            ],
        ]);

        $result = $this->authService->changeInitialPassword(
            $request->email,
            $request->current_password,
            $request->password
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'message' => $result['message'],
        ], 200);
    }
}
