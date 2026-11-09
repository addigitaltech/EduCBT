<?php

namespace App\Http\Controllers;

use App\Domain\System\Support\HealthChecker;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function __invoke(HealthChecker $checker): View
    {
        $checks = $checker->run();

        return view('system.health', [
            'checks' => $checks,
            'overall' => $checker->overall($checks),
        ]);
    }
}
