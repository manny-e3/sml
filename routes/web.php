<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SecurityController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to login
Route::get('/', function () {
    return redirect('/login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Common Dashboard (fallback)
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // Super Admin Routes
    Route::middleware('role:super_admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');
        
        // User Management
        Route::get('/users', function () {
            return view('admin.users.index');
        })->name('admin.users.index');
        
        // System Settings
        Route::get('/settings', function () {
            return view('admin.settings');
        })->name('admin.settings');
        
        // Audit Logs
        Route::get('/audit-logs', function () {
            return view('admin.audit-logs');
        })->name('admin.audit-logs');
    });
    
    // Inputter Routes
    Route::middleware('role:inputter|super_admin')->prefix('inputter')->group(function () {
        Route::get('/dashboard', function () {
            return view('inputter.dashboard');
        })->name('inputter.dashboard');
        
        // Securities Management (Inputter can create/edit)
        Route::resource('securities', SecurityController::class)->except(['destroy']);
        
        // My Submissions
        Route::get('/my-submissions', function () {
            return view('inputter.submissions');
        })->name('inputter.submissions');
    });
    
    // Authoriser Routes
    Route::middleware('role:authoriser|super_admin')->prefix('authoriser')->group(function () {
        Route::get('/dashboard', function () {
            return view('authoriser.dashboard');
        })->name('authoriser.dashboard');
        
        // Pending Approvals
        Route::get('/pending-approvals', function () {
            return view('authoriser.pending-approvals');
        })->name('authoriser.pending-approvals');
        
        // Approve/Reject Actions
        Route::post('/approve/{id}', function () {
            // Approval logic
        })->name('authoriser.approve');
        
        Route::post('/reject/{id}', function () {
            // Rejection logic
        })->name('authoriser.reject');
    });
    
    // Shared Routes (All authenticated users)
    Route::middleware('permission:view-securities')->group(function () {
        // View Securities (Read-only for all)
        Route::get('/securities', [SecurityController::class, 'index'])->name('securities.index');
        Route::get('/securities/{security}', [SecurityController::class, 'show'])->name('securities.show');
        
        // Export
        Route::get('/securities/export/excel', [SecurityController::class, 'exportExcel'])->name('securities.export.excel');
        Route::get('/securities/export/pdf', [SecurityController::class, 'exportPdf'])->name('securities.export.pdf');
    });
    
    // Import (Inputter and Super Admin only)
    Route::middleware('permission:create-securities')->group(function () {
        Route::post('/securities/import', [SecurityController::class, 'import'])->name('securities.import');
    });
    
    // API Routes for AJAX
    Route::prefix('api')->group(function () {
        // Get product types by market category
        Route::get('/product-types/{marketCategoryId}', function ($marketCategoryId) {
            return \App\Models\ProductType::where('market_category_id', $marketCategoryId)
                ->where('is_active', true)
                ->get();
        })->name('api.product-types');
        
        // Calculate tenor
        Route::post('/calculate-tenor', function (\Illuminate\Http\Request $request) {
            $issueDate = \Carbon\Carbon::parse($request->issue_date);
            $maturityDate = \Carbon\Carbon::parse($request->maturity_date);
            return response()->json(['tenor' => $maturityDate->diffInYears($issueDate)]);
        })->name('api.calculate-tenor');
    });
});
