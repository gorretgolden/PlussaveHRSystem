<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public const IDLE_LIMIT_SECONDS = 1800;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->active || $user->isLocked()) {
            return $this->signOut($request, $user, $user->isLocked()
                ? 'Your account is locked. Ask the system administrator to unlock it.'
                : 'Your account is not active. Ask the system administrator.');
        }

        if ($user->last_activity_at && $user->last_activity_at->copy()->addSeconds(self::IDLE_LIMIT_SECONDS)->isPast()) {
            AuditLog::record($user->employee_id, $user->role, 'Signed out', null, $user->name.' signed out after 30 minutes idle');

            return $this->signOut($request, $user, 'You were signed out after 30 minutes idle. Sign in again.');
        }

        $user->forceFill(['last_activity_at' => now()])->saveQuietly();

        if ($user->must_change_password && ! $request->routeIs('password.*') && ! $request->routeIs('logout')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Choose a new password before continuing.'], 423)
                : redirect()->route('password.edit');
        }

        return $next($request);
    }

    private function signOut(Request $request, $user, string $message): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? response()->json(['message' => $message], 401)
            : redirect()->route('login')->withErrors(['username' => $message]);
    }
}
