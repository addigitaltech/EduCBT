<?php

namespace App\Domain\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = ['group', 'key', 'value', 'is_encrypted', 'updated_by'];

    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean'];
    }
}
