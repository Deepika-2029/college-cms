<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class ApiKey extends Model
{
    protected $fillable = [
        'name', 'key_hash', 'key_prefix', 'table_name', 'is_active',
        'request_count', 'last_used_at', 'created_by', 'expires_at',
        'data_limit', 'data_sort'
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    protected $hidden = ['key_hash'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        if (! $this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        return true;
    }

    /**
     * Verify an API key against its stored hash.
     *
     * Uses a key_prefix (first 8 chars) stored in plaintext to pre-filter
     * candidates before expensive Hash::check(). This avoids O(n) bcrypt
     * over all active keys on every request.
     *
     * @param string $plainKey  The raw API key from the request header
     * candidates before expensive \Illuminate\Support\Facades\Crypt::decryptString().
     *
     * @param string $key  The raw API key from the request header
     * @return self|null        The ApiKey model if active and valid
     */
    public static function verify(string $key): ?self
    {
        return \Illuminate\Support\Facades\Cache::remember("api_key_auth_" . md5($key), 300, function () use ($key) {
            $prefix = substr($key, 0, 8);
            $candidates = static::where('is_active', true)->where('key_prefix', $prefix)->get();

            foreach ($candidates as $candidate) {
                try {
                    if (\Illuminate\Support\Facades\Crypt::decryptString($candidate->key_hash) === $key) {
                        return $candidate;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            return null;
        });
    }

    /**
     * Creates a new API Key securely.
     * Returns ['key' => 'raw_key_show_once', 'model' => ApiKey].
     * We use Crypt instead of Hash so that the user can copy the key anytime from the dashboard.
     */
    public static function generate(string $name, string $tableName, int $userId, array $options = []): array
    {
        $rawKey = bin2hex(random_bytes(32)); // 64-char hex string
        $prefix = substr($rawKey, 0, 8);

        $attrs = array_merge([
            'name'       => $name,
            'key_hash'   => \Illuminate\Support\Facades\Crypt::encryptString($rawKey),
            'key_prefix' => $prefix,
            'table_name' => $tableName,
            'created_by' => $userId,
            'is_active'  => true,
            'data_limit' => $options['data_limit'] ?? null,
            'data_sort'  => $options['data_sort'] ?? null,
        ], $options);

        $model = static::create($attrs);

        return [
            'key'   => $rawKey,   // ← Show to user ONCE only
            'model' => $model,
        ];
    }

    public function recordUsage(): void
    {
        $this->request_count++;
        // Use an atomic query to avoid firing heavy Eloquent update events on every API hit
        \Illuminate\Support\Facades\DB::table($this->getTable())
            ->where('id', $this->id)
            ->increment('request_count', 1, ['last_used_at' => now()]);
    }

    public function getDecryptedKeyAttribute(): string
    {
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($this->key_hash);
        } catch (\Exception $e) {
            return 'Encrypted (Cannot Decrypt)';
        }
    }
}
