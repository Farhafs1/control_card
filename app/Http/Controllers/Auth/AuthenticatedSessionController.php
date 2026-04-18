<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Attempt login
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Check if user is active (since you have this logic in Fortify, let's keep it here too)
        if (! Auth::user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Your account is deactivated. Please contact the administrator.',
            ]);
        }

        $request->session()->regenerate();

        /**
         * THE FIX:
         * Redirect to '/dashboard' which is our Traffic Warden in web.php.
         * We remove 'intended' to stop it from remembering old Admin pages.
         */
        return redirect('/dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}