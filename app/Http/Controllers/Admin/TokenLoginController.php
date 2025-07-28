<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class TokenLoginController extends Controller
{
    /**
     * Consume a Sanctum token from the query string,
     * log in that tokenable user, and start a session.
     */
    public function loginByToken(Request $request)
    {
        $token = $request->query('token');
        if (! $token) abort(403, 'Token required');

        $pat = PersonalAccessToken::findToken($token);
        if (! $pat) abort(403, 'Invalid or expired token');

        // Log in as that user
        Auth::login($pat->tokenable);
        $request->session()->regenerate();

        // Redirect into the user’s dashboard
        return redirect()->route('user-dashboard');
    }


}
