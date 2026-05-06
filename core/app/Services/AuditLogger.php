<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function __construct(
        private ?Request         $request  = null,
        private ?GeoLookup       $geo      = null,
        private ?AnomalyDetector $anomaly  = null,
    ) {}

    /** Fields that must never appear in audit log old/new values */
    private const SENSITIVE_FIELDS = [
        'password', 'remember_token', 'key_hash', 'api_key',
        'secret', 'token', 'api_secret', 'current_password',
        'new_password', 'password_confirmation',
    ];

    public function log(
        string  $action,
        ?string $targetType  = null,
        ?string $targetId    = null,
        ?string $targetLabel = null,
        ?array  $oldValues   = null,
        ?array  $newValues   = null,
    ): void {
        try {
            if ($this->isVendorRequest()) return;

            $user = Auth::user();
            $ip   = $this->request?->ip();

            $geo = ['country' => null, 'city' => null];
            if ($ip && $this->geo) {
                $geo = $this->geo->lookup($ip);
            }

            $data = [
                'user_id'           => $user?->id,
                'user_name'         => $user?->name,
                'user_email'        => $user?->email ?? (str_contains($action, 'login') ? $targetLabel : null),
                'user_role'         => $user?->role,
                'action'            => $action,
                'target_type'       => $targetType,
                'target_id'         => $targetId,
                'target_label'      => $targetLabel,
                'old_values'        => $this->scrubSensitive($oldValues),
                'new_values'        => $this->scrubSensitive($newValues),
                'ip_address'        => $ip,
                'user_agent'        => $this->request?->userAgent(),
                'country'           => $geo['country'],
                'city'              => $geo['city'],
                'is_suspicious'     => false,
                'suspicious_reason' => null,
            ];

            if ($this->anomaly) {
                [$suspicious, $reason] = $this->anomaly->analyse($data);
                $data['is_suspicious']     = $suspicious;
                $data['suspicious_reason'] = $reason;
            }

            AuditLog::create($data);

        } catch (\Throwable) {}
    }

    /**
     * Remove sensitive fields from value arrays before storing in the audit log.
     * Prevents passwords, tokens, and hashes from ever being written to the DB.
     */
    private function scrubSensitive(?array $values): ?array
    {
        if ($values === null) return null;
        foreach (self::SENSITIVE_FIELDS as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = '[REDACTED]';
            }
        }
        return $values;
    }

    private function isVendorRequest(): bool
    {
        if (! $this->request) return false;
        $path = $this->request->path();

        foreach (['_debugbar','_ignition','telescope','horizon','livewire','sanctum','__clockwork','vendor/','storage/','up'] as $p) {
            if (str_starts_with($path, $p)) return true;
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($ext, ['js','css','png','jpg','ico','svg','woff','woff2','map'], true);
    }
}
