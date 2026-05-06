<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TablePermission;
use App\Models\TablesRegistry;
use App\Models\User;
use Illuminate\Http\Request;

class TablePermissionController extends Controller
{
    /**
     * Show permission matrix: all tables × all admin users.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $tables = TablesRegistry::orderBy('table_name')->get();
        $admins = User::where('role', 'admin')->orderBy('name')->get();

        // Build a matrix: [user_id][table_name] => TablePermission|null
        $perms = TablePermission::whereIn('user_id', $admins->pluck('id'))
            ->get()
            ->groupBy('user_id')
            ->map(fn($rows) => $rows->keyBy('table_name'));

        return view('admin.permissions.index', compact('tables', 'admins', 'perms'));
    }

    /**
     * Show permissions for a single user.
     */
    public function show(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_if($user->isSuperAdmin(), 422, 'Super admins always have full access.');

        $tables = TablesRegistry::orderBy('table_name')->get();
        $perms  = TablePermission::where('user_id', $user->id)
            ->get()
            ->keyBy('table_name');

        return view('admin.permissions.user', compact('user', 'tables', 'perms'));
    }

    /**
     * Save permissions for a single user (full overwrite from form).
     */
    public function save(Request $request, User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_if($user->isSuperAdmin(), 422, 'Cannot modify Super Admin permissions.');

        $tables = TablesRegistry::pluck('table_name');

        foreach ($tables as $table) {
            $key = str_replace(['-', '.'], '_', $table); // safe form key
            TablePermission::updateOrCreate(
                ['user_id' => $user->id, 'table_name' => $table],
                [
                    'can_view'   => $request->boolean("perm.{$key}.can_view"),
                    'can_create' => $request->boolean("perm.{$key}.can_create"),
                    'can_edit'   => $request->boolean("perm.{$key}.can_edit"),
                    'can_delete' => $request->boolean("perm.{$key}.can_delete"),
                ]
            );
        }

        return back()->with('success', "Permissions saved for {$user->name}.");
    }

    /**
     * Quick-toggle a single permission cell via AJAX.
     */
    public function toggle(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $request->validate([
            'user_id'    => ['required', 'exists:users,id'],
            'table_name' => ['required', 'string'],
            'ability'    => ['required', 'in:can_view,can_create,can_edit,can_delete'],
        ]);

        $userId = $request->integer('user_id');
        $table  = $request->input('table_name');
        $ability = $request->input('ability');

        $perm = TablePermission::firstOrCreate(
            ['user_id' => $userId, 'table_name' => $table],
            ['can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false]
        );

        $perm->$ability = ! $perm->$ability;
        $perm->save();

        return response()->json(['value' => $perm->$ability]);
    }

    /**
     * Grant all abilities (view/create/edit/delete) to a user for all tables.
     */
    public function grantAll(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_if($user->isSuperAdmin(), 422);

        $tables = TablesRegistry::pluck('table_name');
        foreach ($tables as $table) {
            TablePermission::updateOrCreate(
                ['user_id' => $user->id, 'table_name' => $table],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true]
            );
        }

        return back()->with('success', "Full access granted to {$user->name} for all tables.");
    }

    /**
     * Revoke all abilities for a user.
     */
    public function revokeAll(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_if($user->isSuperAdmin(), 422);

        TablePermission::where('user_id', $user->id)->delete();

        return back()->with('success', "All table permissions revoked for {$user->name}.");
    }
}
