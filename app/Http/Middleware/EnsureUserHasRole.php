<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:shipper') or ->middleware('role:admin,operator').
 * Deactivated accounts are signed out on their next request.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'حساب کاربری شما غیرفعال شده است. لطفاً با پشتیبانی تماس بگیرید.');
        }

        $allowed = array_map(fn (string $role) => UserRole::from($role), $roles);

        if ($allowed !== [] && ! $user->hasRole(...$allowed)) {
            abort(403, 'شما به این بخش دسترسی ندارید.');
        }

        return $next($request);
    }
}
