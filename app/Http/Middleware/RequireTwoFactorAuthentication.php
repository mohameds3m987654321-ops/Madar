<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! filter_var(config('fortify.require_two_factor', false), FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! in_array($user->role, ['admin', 'teacher'], true)) {
            return $next($request);
        }

        if ($request->routeIs('logout') || $request->routeIs('two-factor.*')) {
            return $next($request);
        }

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('two-factor.setup')
                ->with('error', 'يجب إعداد المصادقة الثنائية قبل الوصول إلى هذه الصفحة.');
        }

        if (
            (string) $request->session()->get('two_factor.authenticated_user_id')
            === (string) $user->getAuthIdentifier()
        ) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('error', 'سجّل الدخول وأكمل التحقق الثنائي قبل المتابعة.');
    }
}
