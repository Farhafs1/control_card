<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware('guest')->group(function () {
    // Let Fortify handle the POST 'login' logic. 
    // We only keep this if you need to point to a specific blade file.
    Route::get('login', function () {
        return view('pages::auth.login'); 
    })->name('login');
});

Route::middleware('auth')->group(function () {
    // Standard Logout
    Route::post('logout', function (Request $request) {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');
});