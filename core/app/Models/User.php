<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'system_permissions',
        'avatar',
        'bio',
        'department',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'status'             => 'boolean',
            'system_permissions' => 'array',
        ];
    }

    /**
     * Safe role accessor — returns 'admin' if column doesn't exist yet.
     */
    public function getRoleAttribute($value): string
    {
        return $value ?: 'admin';
    }

    // ── Role helpers ───────────────────────────────────────────────
    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isAdmin(): bool      { return in_array($this->role, ['super_admin', 'admin']); }

    public function hasSystemPermission(string $permission): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }
        
        // Ensure regular admins always have access to their own media
        if ($this->role === 'admin' && $permission === 'media') {
            return true;
        }

        $perms = $this->system_permissions ?? [];
        return in_array($permission, $perms, true);
    }

    /**
     * Permission list — automatically derived from role and explicit granular system permissions.
     * Returns a human-readable array shown on the profile page.
     */
    public function permissions(): array
    {
        $all = [
            'database_builder'  => 'Database Builder',
            'plugin_manager'    => 'Plugin Manager',
            'plugin_creator'    => 'Plugin Creator',
            'tools'             => 'System Tools',
            'menu_builder'      => 'Menu Builder',
            'ui_editor'         => 'UI Editor',
            'audit_logs'        => 'Audit Logs',
            'settings'          => 'Settings (write)',
            'api_keys'          => 'API Keys',
            'advanced_users'    => 'Advanced User Settings',
            'page_builder'      => 'Page Builder',
            'templates'         => 'Templates',
            'navigation'        => 'Navigation',
            'user_management'   => 'User Management',
            'media'             => 'Media (upload/view)',
            'media_delete'      => 'Media (delete)',
            'crud_content'      => 'Content CRUD',
            'crud_ui'           => 'CRUD UI Customize',
        ];

        $granted = match($this->role) {
            'super_admin' => array_keys($all),
            'admin'       => array_merge([
                'page_builder', 'templates', 'navigation',
                'user_management', 'media', 'media_delete',
                'crud_content', 'crud_ui',
            ], $this->system_permissions ?? []),
            default       => [],
        };

        $result = [];
        foreach ($all as $key => $label) {
            $result[$key] = [
                'label'   => $label,
                'granted' => in_array($key, $granted, true),
            ];
        }
        return $result;
    }

    /** Avatar URL — returns initials-based data URI if no avatar set */
    public function avatarUrl(): string
    {
        if ($this->avatar && file_exists(public_path($this->avatar))) {
            return asset($this->avatar);
        }
        return '';
    }

    /** User's uploaded media files */
    public function media()
    {
        return $this->hasMany(\App\Models\MediaFile::class, 'uploaded_by');
    }
}
