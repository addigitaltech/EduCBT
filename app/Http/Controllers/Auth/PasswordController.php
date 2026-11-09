<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Actions\ChangeOwnPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.password', [
            'forced' => (bool) auth()->user()->must_change_password,
        ]);
    }

    public function update(ChangePasswordRequest $request, ChangeOwnPassword $change): RedirectResponse
    {
        $change->handle($request->user(), (string) $request->input('password'));

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Your password was changed.');
    }
}
