<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BranchLoginEnabledMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $branch = $user->branch;

        if (! $user->is_active || (! $user->isAdmin() && (! $user->isBranchUser() || ! $branch || ! $branch->is_login_enabled))) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => ! $user->is_active ? 'Akun sedang dinonaktifkan oleh admin.' : 'Akses cabang sedang dinonaktifkan oleh admin.',
            ]);
        }

        return $next($request);
    }
}
