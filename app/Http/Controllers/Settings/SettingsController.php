<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Audit\AuditLogger;
use App\Domain\Settings\SettingDefinitions;
use App\Domain\Settings\SettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AppearanceSettingsRequest;
use App\Http\Requests\SecuritySettingsRequest;
use App\Support\Navigation\SettingsTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settings,
        private AuditLogger $audit,
    ) {}

    /** Settings is one sidebar entry: open the first tab this person may use. */
    public function index(): RedirectResponse
    {
        $route = SettingsTabs::firstRoute(auth()->user());

        abort_if($route === null, 403);

        return redirect()->route($route);
    }

    public function security(): View
    {
        return view('settings.security', [
            'values' => [
                'max_login_attempts' => $this->settings->get('security', 'max_login_attempts'),
                'lockout_minutes' => $this->settings->get('security', 'lockout_minutes'),
                'password_min_length' => $this->settings->get('security', 'password_min_length'),
            ],
        ]);
    }

    public function updateSecurity(SecuritySettingsRequest $request): RedirectResponse
    {
        $keys = ['max_login_attempts', 'lockout_minutes', 'password_min_length'];
        $before = [];
        $after = [];

        foreach ($keys as $key) {
            $before[$key] = $this->settings->get('security', $key);
            $after[$key] = (int) $request->validated($key);
            $this->settings->set('security', $key, $after[$key], false, $request->user());
        }

        if ($before !== $after) {
            $this->audit->record('settings.security_changed', 'settings', null, $before, $after, $request->user());
        }

        return redirect()->route('settings.security')->with('status', 'Security settings saved.');
    }

    public function appearance(): View
    {
        return view('settings.appearance', [
            'values' => [
                'display_name' => $this->settings->get('appearance', 'display_name'),
                'accent' => $this->settings->get('appearance', 'accent'),
            ],
            'accents' => SettingDefinitions::ACCENTS,
        ]);
    }

    public function updateAppearance(AppearanceSettingsRequest $request): RedirectResponse
    {
        $before = [
            'display_name' => $this->settings->get('appearance', 'display_name'),
            'accent' => $this->settings->get('appearance', 'accent'),
        ];
        $after = [
            'display_name' => $request->validated('display_name') ?: null,
            'accent' => $request->validated('accent'),
        ];

        foreach ($after as $key => $value) {
            $this->settings->set('appearance', $key, $value, false, $request->user());
        }

        if ($before !== $after) {
            $this->audit->record('settings.appearance_changed', 'settings', null, $before, $after, $request->user());
        }

        return redirect()->route('settings.appearance')->with('status', 'Appearance saved.');
    }
}
