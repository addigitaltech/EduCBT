<?php

namespace App\Domain\Identity\Models;

use App\Domain\Access\Models\Role;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\Enums\UserType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'uuid',
        'user_type',
        'login_id',
        'email',
        'name',
        'password',
        'status',
        'must_change_password',
        'failed_attempts',
        'locked_until',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    /** @var list<string>|null */
    private ?array $permissionCache = null;

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->uuid ??= (string) Str::uuid();
        });
    }

    /** Addresses in the browser use the public uuid, never the numeric id. */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'user_type' => UserType::class,
            'status' => UserStatus::class,
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'failed_attempts' => 'integer',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** Sign-in IDs are stored lower-case so lookups behave the same on every database. */
    protected function loginId(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Str::lower(trim($value)),
        );
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withPivot(['assigned_by', 'created_at']);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isTemporarilyLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function canSignIn(): bool
    {
        return $this->isActive() && ! $this->isTemporarilyLocked();
    }

    /** @return list<string> */
    public function permissionKeys(): array
    {
        return $this->permissionCache ??= DB::table('permissions')
            ->join('permission_role', 'permissions.id', '=', 'permission_role.permission_id')
            ->join('role_user', 'permission_role.role_id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $this->getKey())
            ->pluck('permissions.key')
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(string $key): bool
    {
        return in_array($key, $this->permissionKeys(), true);
    }

    public function forgetPermissionCache(): void
    {
        $this->permissionCache = null;
    }
}
