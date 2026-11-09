<?php

namespace App\Http\Middleware;

use App\Domain\System\Models\SystemIdentity;
use App\Support\Profile\Profile;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops the application when the installation record is missing or was created
 * for the other profile. A deployment is one profile for life.
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $identity = SystemIdentity::current();
        } catch (QueryException) {
            $identity = null;
        }

        if ($identity === null) {
            return response()->view('errors.not-installed', [], 503);
        }

        if (! $identity->matches(Profile::current())) {
            return response()->view('errors.profile-mismatch', [
                'installedAs' => $identity->profile,
                'configuredAs' => Profile::current()->value,
            ], 500);
        }

        return $next($request);
    }
}
