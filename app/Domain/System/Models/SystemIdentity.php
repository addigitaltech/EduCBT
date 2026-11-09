<?php

namespace App\Domain\System\Models;

use App\Support\Profile\Profile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The single row that says which profile this installation was set up as.
 */
class SystemIdentity extends Model
{
    public const ROW_ID = 1;

    public $incrementing = false;

    protected $table = 'system_identity';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    protected $fillable = ['id', 'installation_uuid', 'profile', 'school_uuid', 'schema_version', 'installed_at'];

    protected function casts(): array
    {
        return ['installed_at' => 'datetime'];
    }

    public static function current(): ?self
    {
        return static::query()->find(self::ROW_ID);
    }

    public static function install(Profile $profile): self
    {
        return static::query()->create([
            'id' => self::ROW_ID,
            'installation_uuid' => (string) Str::uuid(),
            'profile' => $profile->value,
            'schema_version' => '1',
            'installed_at' => now(),
        ]);
    }

    public function matches(Profile $profile): bool
    {
        return $this->profile === $profile->value;
    }
}
