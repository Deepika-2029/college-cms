@extends('admin.layout')

@section('title', "Permissions: {$user->name}")
@section('page-title', "Permissions: {$user->name}")

@section('topbar-actions')
<a href="{{ route('admin.permissions.index') }}" class="btn-cms btn-cms-secondary btn-cms-sm">
  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
  Back to Permissions
</a>
@endsection

@push('styles')
<style>
    .perm-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
    .perm-table th, .perm-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); text-align: center; }
    .perm-table th:first-child, .perm-table td:first-child { text-align: left; background: var(--surface); z-index: 10; border-right: 1px solid var(--border); font-weight: 500;}
    .perm-table thead th { background: var(--surface-2); border-top: 1px solid var(--border); border-bottom: 2px solid var(--border); font-weight: 600; color: var(--text-2);}
    .perm-table tr:last-child td { border-bottom: none; }
    
    .chk-wrap { display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
    .chk-wrap input[type="checkbox"] { width: 1.1rem; height: 1.1rem; cursor: pointer; accent-color: var(--accent); }
    
    .table-row-hover:hover { background: rgba(0,0,0,0.02); }
</style>
@endpush

@section('content')

@if($tables->isEmpty())
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
        <div class="empty-state-title">No Tables Exist</div>
        <div class="empty-state-desc">Create tables in the Database Builder first.</div>
    </div>
@else
    
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end;">
        <div style="font-size: 0.9rem; color: var(--text-2);">
            Manage capabilities for <strong>{{ $user->name }}</strong>. 
            <br>Changes made below require saving at the bottom of the page, or checking/unchecking boxes will auto-save if JS is enabled.
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" class="btn-cms btn-cms-secondary btn-cms-sm" onclick="setAll(true)">Check All</button>
            <button type="button" class="btn-cms btn-cms-secondary btn-cms-sm" onclick="setAll(false)">Uncheck All</button>
        </div>
    </div>

    <form action="{{ route('admin.permissions.save', $user->id) }}" method="POST" id="perm-form">
        @csrf
        <div style="overflow-x: auto;">
        <table class="perm-table">
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th><label class="chk-wrap"><input type="checkbox" onclick="toggleColumn(this, 'view')"> View</label></th>
                    <th><label class="chk-wrap"><input type="checkbox" onclick="toggleColumn(this, 'create')"> Create</label></th>
                    <th><label class="chk-wrap"><input type="checkbox" onclick="toggleColumn(this, 'edit')"> Edit</label></th>
                    <th><label class="chk-wrap"><input type="checkbox" onclick="toggleColumn(this, 'delete')"> Delete</label></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tables as $table)
                    @php 
                        $tname = $table->table_name;
                        $safeKey = str_replace(['-', '.'], '_', $tname);
                        $p = $perms[$tname] ?? null;
                    @endphp
                    <tr class="table-row-hover">
                        <td>{{ $tname }}</td>
                        <td>
                            <label class="chk-wrap">
                                <input type="checkbox" name="perm[{{ $safeKey }}][can_view]" value="1" data-table="{{ $tname }}" data-ability="can_view" class="chk-view" {{ ($p && $p->can_view) ? 'checked' : '' }}>
                            </label>
                        </td>
                        <td>
                            <label class="chk-wrap">
                                <input type="checkbox" name="perm[{{ $safeKey }}][can_create]" value="1" data-table="{{ $tname }}" data-ability="can_create" class="chk-create" {{ ($p && $p->can_create) ? 'checked' : '' }}>
                            </label>
                        </td>
                        <td>
                            <label class="chk-wrap">
                                <input type="checkbox" name="perm[{{ $safeKey }}][can_edit]" value="1" data-table="{{ $tname }}" data-ability="can_edit" class="chk-edit" {{ ($p && $p->can_edit) ? 'checked' : '' }}>
                            </label>
                        </td>
                        <td>
                            <label class="chk-wrap">
                                <input type="checkbox" name="perm[{{ $safeKey }}][can_delete]" value="1" data-table="{{ $tname }}" data-ability="can_delete" class="chk-delete" {{ ($p && $p->can_delete) ? 'checked' : '' }}>
                            </label>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        
        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn-cms btn-cms-primary">Save All Permissions</button>
        </div>
    </form>

@endif

@endsection

@push('scripts')
<script>
    const userId = {{ $user->id }};
    const toggleUrl = '{{ route("admin.permissions.toggle") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // AJAX toggle for instant save
    document.querySelectorAll('.perm-table tbody input[type="checkbox"]').forEach(chk => {
        chk.addEventListener('change', async function() {
            const table = this.dataset.table;
            const ability = this.dataset.ability;
            const isChecked = this.checked;
            
            // disable temporarily
            this.disabled = true;
            
            try {
                const res = await fetch(toggleUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ user_id: userId, table_name: table, ability: ability })
                });
                
                if (!res.ok) throw new Error('Failed to toggle');
                
                const data = await res.json();
                this.checked = data.value; // set to verified state from server
                
                // Show little toast
                if (typeof window.cmsToast === 'function') {
                    window.cmsToast('Permission updated', 'success');
                }
            } catch (err) {
                console.error(err);
                this.checked = !isChecked; // revert
                if (typeof window.cmsToast === 'function') {
                    window.cmsToast('Failed to update permission', 'error');
                } else {
                    cmsToast('Failed to update permission', 'error');
                }
            } finally {
                this.disabled = false;
            }
        });
    });

    // Check/Uncheck all helper
    function setAll(checked) {
        document.querySelectorAll('.perm-table tbody input[type="checkbox"]').forEach(chk => {
            if(chk.checked !== checked) {
                chk.checked = checked;
                // Dispatch event so AJAX auto-save fires
                chk.dispatchEvent(new Event('change'));
            }
        });
    }

    // Toggle column helper
    function toggleColumn(headerCheckbox, colClass) {
        const checked = headerCheckbox.checked;
        document.querySelectorAll('.chk-' + colClass).forEach(chk => {
            if(chk.checked !== checked) {
                chk.checked = checked;
                chk.dispatchEvent(new Event('change'));
            }
        });
    }
</script>
@endpush
