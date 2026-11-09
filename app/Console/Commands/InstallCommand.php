<?php

namespace App\Console\Commands;

use App\Domain\Access\Models\Role;
use App\Domain\Access\RoleCatalog;
use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Domain\System\Models\SystemIdentity;
use App\Support\Profile\Profile;
use Database\Seeders\AccessSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'app:install
        {--login= : Sign-in ID of the first administrator}
        {--name= : Full name of the first administrator}
        {--email= : Optional email address}
        {--password= : Password (leave out to generate a temporary one)}';

    protected $description = 'Set up this installation for its profile and create the first administrator';

    public function handle(AuditLogger $audit): int
    {
        $profile = Profile::current();

        try {
            $existing = SystemIdentity::current();
        } catch (QueryException) {
            $this->error('The database is not ready. Run "php artisan migrate" first.');

            return self::FAILURE;
        }

        if ($existing !== null) {
            $this->error('This installation is already set up as: '.$existing->profile.'. Nothing was changed.');

            return self::FAILURE;
        }

        $this->info('Setting up this installation as: '.$profile->label());

        $login = (string) ($this->option('login') ?: $this->ask('Sign-in ID for the first administrator'));
        $name = (string) ($this->option('name') ?: $this->ask('Full name of the first administrator'));
        $email = $this->option('email') ?: null;
        $password = (string) ($this->option('password') ?: $this->secret('Password (press Enter to generate a temporary one)'));
        $generated = $password === '';

        if ($generated) {
            $password = Str::password(14, symbols: false);
        }

        $validator = Validator::make(
            ['login' => mb_strtolower(trim($login)), 'name' => $name, 'email' => $email, 'password' => $password],
            [
                'login' => ['required', 'min:3', 'max:120', 'regex:/^[a-z0-9._@\/-]+$/'],
                'name' => ['required', 'max:160'],
                'email' => ['nullable', 'email', 'max:190'],
                'password' => ['required', 'min:10'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($profile, $login, $name, $email, $password, $generated, $audit): void {
            SystemIdentity::install($profile);
            (new AccessSeeder)->run();

            $user = User::query()->create([
                'user_type' => UserType::staffFor($profile),
                'login_id' => $login,
                'email' => $email,
                'name' => $name,
                'password' => $password,
                'status' => UserStatus::Active,
                'must_change_password' => $generated,
            ]);

            $role = Role::query()
                ->where('scope', $profile->value)
                ->where('slug', RoleCatalog::administratorSlug($profile))
                ->firstOrFail();

            $user->roles()->attach($role->id, ['assigned_by' => null, 'created_at' => now()]);

            $audit->record('system.installed', 'system', null, null, [
                'profile' => $profile->value,
                'administrator' => $user->login_id,
            ], $user);
        });

        $this->info('Done. The first administrator can now sign in.');

        if ($generated) {
            $this->warn('Temporary password (shown once, change it at first sign-in): '.$password);
        }

        return self::SUCCESS;
    }
}
