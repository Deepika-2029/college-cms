<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'user_email', 'user_role',
        'action', 'target_type', 'target_id', 'target_label',
        'old_values', 'new_values',
        'ip_address', 'user_agent',
        'country', 'city',
        'is_suspicious', 'suspicious_reason', 'blocked',
    ];

    protected $casts = [
        'old_values'    => 'array',
        'new_values'    => 'array',
        'is_suspicious' => 'boolean',
        'blocked'       => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionBadgeClass(): string
    {
        return match(true) {
            str_contains($this->action, 'login.failed') => 'badge-red',
            str_contains($this->action, 'login')        => 'badge-blue',
            str_contains($this->action, 'logout')       => 'badge-gray',
            str_contains($this->action, 'created')      => 'badge-green',
            str_contains($this->action, 'updated')      => 'badge-yellow',
            str_contains($this->action, 'deleted')      => 'badge-red',
            str_contains($this->action, 'blocked')      => 'badge-red',
            str_contains($this->action, 'denied')       => 'badge-red',
            default                                     => 'badge-purple',
        };
    }

    public function actionIcon(): string
    {
        return match(true) {
            str_contains($this->action, 'login.failed') => '⛔',
            str_contains($this->action, 'login')        => '🔑',
            str_contains($this->action, 'logout')       => '🚪',
            str_contains($this->action, 'created')      => '✚',
            str_contains($this->action, 'updated')      => '✎',
            str_contains($this->action, 'deleted')      => '✕',
            str_contains($this->action, 'blocked')      => '🚫',
            str_contains($this->action, 'denied')       => '🛑',
            default                                     => '•',
        };
    }
}
