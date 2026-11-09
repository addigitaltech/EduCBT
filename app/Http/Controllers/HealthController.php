<?php

namespace App\Http\Controllers;

use App\Domain\System\Support\HealthChecker;
use App\Support\Profile\Profile;
use Illuminate\Http\JsonResponse;

/** Minimal public check for load balancers and monitors. It exposes nothing sensitive. */
class HealthController extends Controller
{
    public function __invoke(HealthChecker $checker): JsonResponse
    {
        $up = $checker->databaseIsUp();

        return response()->json([
            'status' => $up ? 'ok' : 'down',
            'profile' => Profile::current()->value,
            'version' => config('profile.version'),
        ], $up ? 200 : 503);
    }
}
