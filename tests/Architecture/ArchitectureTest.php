<?php

/*
 * Architecture rules. They fail the build when someone breaks a decision the
 * whole design depends on: profile isolation, thin controllers, and business
 * logic living in the domain layer.
 */

arch('central profile code never uses school profile code')
    ->expect('App\Profiles\Central')
    ->not->toUse('App\Profiles\School');

arch('school profile code never uses central profile code')
    ->expect('App\Profiles\School')
    ->not->toUse('App\Profiles\Central');

arch('shared domain code does not depend on a profile')
    ->expect('App\Domain')
    ->not->toUse('App\Profiles');

arch('shared domain code does not depend on the web layer')
    ->expect('App\Domain')
    ->not->toUse('App\Http');

arch('controllers keep database and query logic out')
    ->expect('App\Http\Controllers')
    ->not->toUse(['Illuminate\Support\Facades\DB', 'Illuminate\Support\Facades\Schema']);

arch('actions are final classes')
    ->expect(['App\Domain\Identity\Actions', 'App\Domain\Access\Actions'])
    ->toBeFinal();

arch('enums are enums')
    ->expect('App\Domain\Identity\Enums')
    ->toBeEnums();

arch('middleware has a handle method')
    ->expect('App\Http\Middleware')
    ->toHaveMethod('handle');

arch('no debugging helpers are left in the code')
    ->expect(['dd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsed();
