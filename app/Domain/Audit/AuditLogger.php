<?php

namespace App\Domain\Audit;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Identity\Models\User;
use App\Support\Profile\Profile;
use Illuminate\Support\Facades\Auth;

final class AuditLogger
{
    /** Keys whose values are never written to the audit log. */
    private const REDACTED_KEYS = ['password', 'password_confirmation', 'current_password', 'remember_token', 'token', 'secret', 'api_key', 'temp_password'];

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        string $action,
        ?string $entityType = null,
        ?string $entityUuid = null,
        ?array $before = null,
        ?array $after = null,
        ?User $actor = null,
    ): AuditLog {
        $actor ??= Auth::user();
        $request = request();

        return AuditLog::create([
            'occurred_at' => now(),
            'actor_uuid' => $actor?->uuid,
            'actor_label' => $actor !== null ? $actor->name.' ('.$actor->login_id.')' : null,
            'action' => $action,
            'entity_type' => $entityType ?? 'system',
            'entity_uuid' => $entityUuid,
            'before_json' => $before === null ? null : $this->redact($before),
            'after_json' => $after === null ? null : $this->redact($after),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() === null ? null : mb_substr($request->userAgent(), 0, 255),
            'request_id' => $request->attributes->get('request_id'),
            'profile' => Profile::current()->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::REDACTED_KEYS, true)) {
                $data[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }
}
