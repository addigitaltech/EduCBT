<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** People with a temporary password must choose their own before using the system. */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            return redirect()->route('password.edit')
                ->with('status', 'Choose a new password to continue.');
        }

        return $next($request);
    }
}
