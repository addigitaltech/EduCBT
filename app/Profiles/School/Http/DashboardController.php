<?php

namespace App\Profiles\School\Http;

use App\Domain\System\Support\HealthChecker;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(HealthChecker $checker): View
    {
        $checks = $checker->run();

        return view('dashboard', [
            'heading' => 'School dashboard',
            'intro' => 'You are signed in to this school server. Students, classes, examinations and results will appear here as they are added.',
            'overall' => $checker->overall($checks),
        ]);
    }
}
