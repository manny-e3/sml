<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

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
        
        // User management routes will be added here in next steps
    });
    
    // Inputter Routes
    Route::middleware('role:inputter')->prefix('inputter')->group(function () {
        Route::get('/dashboard', function () {
            return view('inputter.dashboard');
        })->name('inputter.dashboard');
        
        // Inputter specific routes will be added here
    });
    
    // Authoriser Routes
    Route::middleware('role:authoriser')->prefix('authoriser')->group(function () {
        Route::get('/dashboard', function () {
            return view('authoriser.dashboard');
        })->name('authoriser.dashboard');
        
        // Authoriser specific routes will be added here
    });
});
