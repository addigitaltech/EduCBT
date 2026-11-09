<?php

namespace App\Domain\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

/**
 * Append-only record of important actions. Application code has no update or
 * delete path; retention pruning is a separate, explicit, audited command.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'before_json' => 'array',
            'after_json' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log): void {
            $log->uuid ??= (string) Str::uuid();
        });

        static::updating(function (): void {
            throw new LogicException('Audit log entries cannot be changed.');
        });

        static::deleting(function (): void {
            throw new LogicException('Audit log entries cannot be deleted.');
        });
    }
}
