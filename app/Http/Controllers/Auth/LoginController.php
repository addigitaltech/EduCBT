<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Actions\AttemptLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AttemptLogin $attempt): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $signedIn = $attempt->handle(
            (string) $request->input('login_id'),
            (string) $request->input('password'),
            $request->boolean('remember'),
        );

        if (! $signedIn) {
            RateLimiter::hit($request->throttleKey(), 60);

            // One message for every kind of failure: it never reveals whether the ID exists.
            throw ValidationException::withMessages([
                'login_id' => 'These details do not match an active account. Check them and try again, or ask your administrator for help.',
            ]);
        }

        RateLimiter::clear($request->throttleKey());
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $audit->record('auth.logout', 'user', $user->uuid, null, null, $user);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have signed out.');
    }
}
