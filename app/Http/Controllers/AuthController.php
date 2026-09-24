<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use App\Models\User;
use App\Rules\SafeEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->user()) {
            return redirect($this->dashboardRouteFor($request->user()));
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email:rfc', new SafeEmail, 'max:150'],
            'password' => ['required', 'string', 'max:4096'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $email = mb_strtolower(trim($credentials['email']));

        if (! Auth::attempt(
            ['email' => $email, 'password' => $credentials['password']],
            $request->boolean('remember'),
        )) {
            $user = User::where('email', $email)->first();

            SystemLog::create([
                'user_id' => $user?->id,
                'action' => 'Failed login',
                'description' => 'Authentication attempt failed.',
                'ip_address' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Those credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! $user || ! in_array($user->role, User::ROLES, true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This account is not configured for the predictor.',
            ]);
        }

        SystemLog::create([
            'user_id' => $user->id,
            'action' => 'Logged in',
            'description' => 'Authenticated successfully.',
            'ip_address' => $request->ip(),
        ]);

        // Do not honor an intended URL from a different role's session.
        return redirect($this->dashboardRouteFor($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($user = $request->user()) {
            SystemLog::create([
                'user_id' => $user->id,
                'action' => 'Logged out',
                'description' => 'Session ended.',
                'ip_address' => $request->ip(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function dashboardRouteFor(User $user): string
    {
        return match ($user->role) {
            User::ROLE_ADMIN => route('admin.dashboard'),
            User::ROLE_INSTRUCTOR => route('instructor.dashboard'),
            default => route('student.dashboard'),
        };
    }
}
