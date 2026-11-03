<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'username' => 'No account uses that username.',
            ]);
        }

        if (! $user->active) {
            throw ValidationException::withMessages([
                'username' => 'That account is not active. Ask the system administrator.',
            ]);
        }

        if ($user->isLocked()) {
            throw ValidationException::withMessages([
                'username' => 'This account is locked after too many failed attempts. Try again in '.$user->lockMinutesLeft().' minute'.($user->lockMinutesLeft() === 1 ? '' : 's').'.',
            ]);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            $user->recordFailedLogin();
            AuditLog::record($user->employee_id, $user->role, 'Sign-in failed', null, $user->name.' entered a wrong password from '.$request->ip());

            throw ValidationException::withMessages([
                'password' => $user->isLocked()
                    ? 'That password is not right. The account is now locked for '.max(1, (int) Setting::get('lockout_duration_minutes', 15)).' minutes.'
                    : 'That password is not right. '.$user->attemptsLeft().' attempt'.($user->attemptsLeft() === 1 ? '' : 's').' left before the account locks.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->recordSuccessfulLogin($request->ip());

        AuditLog::record($user->employee_id, $user->role, 'Signed in', null, $user->name.' signed in from '.$request->ip());

        if ($user->must_change_password) {
            return redirect()->route('password.edit');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        AuditLog::record($user?->employee_id, $user?->role, 'Signed out', null, ($user?->name ?? 'User').' signed out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
