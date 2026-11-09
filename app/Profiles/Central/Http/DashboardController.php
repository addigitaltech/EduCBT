<?php

namespace App\Profiles\Central\Http;

use App\Domain\System\Support\HealthChecker;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(HealthChecker $checker): View
    {
        $checks = $checker->run();

        return view('dashboard', [
            'heading' => 'Platform dashboard',
            'intro' => 'You are signed in to the central platform. Schools, the question bank, subscriptions and releases will appear here as they are added.',
            'overall' => $checker->overall($checks),
        ]);
    }
}
