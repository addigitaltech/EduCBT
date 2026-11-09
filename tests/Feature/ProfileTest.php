<?php

use App\Domain\System\Models\SystemIdentity;
use App\Support\Profile\Profile;
use Illuminate\Support\Facades\Route;

it('registers the dashboard from the active profile only', function (): void {
    $action = Route::getRoutes()->getByName('dashboard')->getActionName();

    $expected = Profile::current()->isCentral() ? 'App\\Profiles\\Central\\' : 'App\\Profiles\\School\\';
    $other = Profile::current()->isCentral() ? 'App\\Profiles\\School\\' : 'App\\Profiles\\Central\\';

    expect($action)->toStartWith($expected)->and($action)->not->toStartWith($other);
});

it('shows the profile name in the shell', function (): void {
    $this->actingAs(adminUser())
        ->get('/')
        ->assertOk()
        ->assertSee(Profile::current()->isCentral() ? 'Platform dashboard' : 'School dashboard')
        ->assertSee(Profile::current()->label());
});

it('refuses to run when the installation was created for the other profile', function (): void {
    $other = Profile::current()->isCentral() ? Profile::School : Profile::Central;
    SystemIdentity::query()->update(['profile' => $other->value]);

    $this->get('/login')->assertStatus(500)->assertSee('set up incorrectly');
});

it('asks for setup when the installation record is missing', function (): void {
    SystemIdentity::query()->delete();

    $this->get('/login')->assertStatus(503)->assertSee('Setup is not finished');
});

it('rejects an invalid profile name', function (): void {
    config(['profile.name' => 'banana']);

    expect(fn () => (new \App\Providers\ProfileServiceProvider(app()))->register())
        ->toThrow(RuntimeException::class);
});

it('only offers navigation items the person is allowed to use', function (): void {
    $nobody = userWith([]);

    $this->actingAs($nobody)->get('/')->assertOk()
        ->assertDontSee('System health')
        ->assertDontSee('Settings');

    $this->actingAs(userWith(['system.health.view']))->get('/')->assertOk()
        ->assertSee('System health')
        ->assertDontSee('Settings');
});

it('exposes a minimal public health endpoint', function (): void {
    $this->getJson('/health')
        ->assertOk()
        ->assertJsonStructure(['status', 'profile', 'version'])
        ->assertJson(['status' => 'ok', 'profile' => Profile::current()->value]);
});

it('adds security headers and a request id to responses', function (): void {
    $response = $this->get('/login');

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('X-Request-ID'))->toBeUuid();
});
