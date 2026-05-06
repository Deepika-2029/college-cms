<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TablePermission extends Model
{
    protected $fillable = [
        'user_id', 'table_name',
        'can_view', 'can_create', 'can_edit', 'can_delete',
    ];

    protected $casts = [
        'can_view'   => 'boolean',
        'can_create' => 'boolean',
        'can_edit'   => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a given user has the specified ability on a table.
     * Super admins always pass.
     */
    public static function userCan(int $userId, string $table, string $ability): bool
    {
        $perm = static::where('user_id', $userId)
            ->where('table_name', $table)
            ->first();

        return $perm && $perm->{"can_{$ability}"};
    }

    /**
     * Get all tables a user is allowed to view.
     */
    public static function allowedTablesForUser(int $userId): array
    {
        return static::where('user_id', $userId)
            ->where('can_view', true)
            ->pluck('table_name')
            ->toArray();
    }

    /**
     * Auto-provision permissions for all non-super-admin users when a new table is created.
     * Super admins always have implicit full access.
     */
    public static function provisionForTable(string $tableName): void
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            static::firstOrCreate(
                ['user_id' => $admin->id, 'table_name' => $tableName],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false]
            );
        }
    }

    /**
     * When a new admin user is created, provision them access to all existing tables.
     */
    public static function provisionForUser(int $userId): void
    {
        $tables = TablesRegistry::pluck('table_name');
        foreach ($tables as $table) {
            static::firstOrCreate(
                ['user_id' => $userId, 'table_name' => $table],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false]
            );
        }
    }
}
