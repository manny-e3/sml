<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SecurityController;
use App\Http\Controllers\Api\AuctionResultController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public Routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/verify-reset-token', [AuthController::class, 'verifyResetToken']);
    Route::post('/change-initial-password', [AuthController::class, 'changeInitialPassword']);


    // Protected Routes
    Route::middleware(['auth:sanctum', 'ensure_password_changed'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Securities
        Route::apiResource('securities', SecurityController::class);
        Route::post('/securities/import', [SecurityController::class, 'import']);

        // Auction Results
        Route::apiResource('auction-results', AuctionResultController::class);
        
        // Search
        Route::get('/search', [SearchController::class, 'search']);
        
        // Market Categories (Dropdown)
        Route::get('market-categories/all', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'all']);

        // Admin Routes - User Management with Role-Based Access
        Route::prefix('admin')->group(function () {
            // Inputters and Super Admins can create users
            Route::middleware('role:inputter|super_admin')->group(function () {
                Route::post('users', [\App\Http\Controllers\Api\Admin\UserController::class, 'store']);
                
                // Market Categories Requests
                Route::post('market-categories', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'store']);
                Route::put('market-categories/{marketCategory}', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'update']);
                Route::delete('market-categories/{marketCategory}', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'destroy']);

               
            });

            Route::middleware('role:authoriser|super_admin')->group(function () {
                // Market Categories Approval
                Route::get('pending-market-categories', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'pending']);
                Route::post('pending-market-categories/{pendingMarketCategory}/approve', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'approve']);
                Route::post('pending-market-categories/{pendingMarketCategory}/reject', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'reject']);
                 });
            
            // Authorizers and Super Admins can view and manage pending users
            Route::middleware('role:authoriser|super_admin')->group(function () {
                Route::get('users/pending', [\App\Http\Controllers\Api\Admin\UserController::class, 'pending']);
                Route::post('users/pending/{pendingUser}/approve', [\App\Http\Controllers\Api\Admin\UserController::class, 'approve']);
                Route::post('users/pending/{pendingUser}/reject', [\App\Http\Controllers\Api\Admin\UserController::class, 'reject']);

                
            });
            
            // Super Admins have full access to user management
            Route::middleware('role:super_admin|inputter|authoriser')->group(function () {
                Route::get('users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
                Route::get('users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'show']);
                Route::put('users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'update']);
                Route::delete('users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);
                
                // Market Categories View
                Route::get('market-categories', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'index']);
                Route::get('market-categories/{marketCategory}', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'show']);
                
                // Product Types (existing)
                Route::apiResource('product-types', \App\Http\Controllers\Api\Admin\ProductTypeController::class);
            });
        });
    });
});
