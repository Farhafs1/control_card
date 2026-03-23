<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;

Route::middleware('guest')->group(function () {
    // If you don't have a login controller yet, we can use a basic view for now
    Route::get('login', function () {
        return view('auth.login'); 
    })->name('login');

    // Add a basic post route for login
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', function (Request $request) {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});