<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
    // Protected Routes (All routes now protected by Custom Basic Auth)
    // We removed login/logout endpoints as we are using stateless Basic Auth per request.
    
    // Note: ensure_password_changed middleware might be redundant if we are hardcoding the API User
    // but I'll leave it if you still want to enforce that the System User has changed their password.
    // Use 'auth.basic.custom' for checking the hardcoded credentials
    Route::middleware(['auth.basic.custom'])->group(function () {
        
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
             
            // User Management
            Route::post('users', [\App\Http\Controllers\Api\Admin\UserController::class, 'store']);
            Route::get('users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
            Route::get('users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'show']);
            Route::put('users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'update']);
            Route::delete('users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);
            
            // Pending Users
            Route::get('users/pending', [\App\Http\Controllers\Api\Admin\UserController::class, 'pending']);
            Route::post('users/pending/{pendingUser}/approve', [\App\Http\Controllers\Api\Admin\UserController::class, 'approve']);
            Route::post('users/pending/{pendingUser}/reject', [\App\Http\Controllers\Api\Admin\UserController::class, 'reject']);

            // Market Categories Requests
            Route::post('market-categories', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'store']);
            Route::put('market-categories/{marketCategory}', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'update']);
            Route::delete('market-categories/{marketCategory}', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'destroy']);
            
            // Market Categories View
            Route::get('market-categories', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'index']);
            Route::get('market-categories/{marketCategory}', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'show']);

            // Market Categories Approval
            Route::get('pending-market-categories', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'pending']);
            Route::post('pending-market-categories/{pendingMarketCategory}/approve', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'approve']);
            Route::post('pending-market-categories/{pendingMarketCategory}/reject', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'reject']);
                
            // Product Type Requests
            Route::post('product-types', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'store']);
            Route::put('product-types/{productType}', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'update']);
            Route::delete('product-types/{productType}', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'destroy']);
             
            // Product Types View
            Route::get('product-types', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'index']);
            Route::get('product-types/{productType}', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'show']);

            // Product Types Approval
            Route::get('pending-product-types', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'pending']);
            Route::post('pending-product-types/{pendingProductType}/approve', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'approve']);
            Route::post('pending-product-types/{pendingProductType}/reject', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'reject']);
          
        });
    });
});
