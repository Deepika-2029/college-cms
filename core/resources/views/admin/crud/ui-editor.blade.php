@extends('admin.layout')

@section('title', 'Edit UI — ' . $table)
@section('page-title', 'Edit UI Templates — ' . $table)

@section('topbar-actions')
    <a href="{{ route('admin.crud.index', $table) }}" class="btn btn-secondary">← Back to Table</a>
@endsection

@push('styles')
<link rel="stylesheet" id="page-css" href="{{ route('admin.page-asset', ['crud/style.css']) }}">
@endpush

@section('content')

<div class="card">
    <div style="margin-bottom:1rem;">
        <p style="font-size:0.875rem;color:var(--text-3);line-height:1.6;">
            These templates are loaded when managing the <span class="badge badge-blue">{{ $table }}</span> table.
            Changes are saved immediately and affect the admin interface.
            Files live in <code>resources/crud-ui/{{ $table }}/</code>
        </p>
    </div>

    <form action="{{ route('admin.crud.ui.save', $table) }}" method="POST" id="ui-form">
        @csrf

        <div class="tab-bar">
            @foreach(['list.html' => 'html', 'form.html' => 'html', 'style.css' => 'css', 'script.js' => 'js'] as $filename => $type)
            <button type="button" class="tab-btn {{ $loop->first ? 'active' : '' }}"
                    data-target="{{ $filename }}">
                <span class="file-badge {{ $type }}">{{ $filename }}</span>
            </button>
            @endforeach
        </div>

        @foreach(['list.html', 'form.html', 'style.css', 'script.js'] as $filename)
        <textarea name="files[{{ $filename }}]"
                  id="editor-{{ $filename }}"
                  class="code-editor {{ $loop->first ? 'active' : '' }}"
                  spellcheck="false">{{ $files[$filename] ?? '' }}</textarea>
        @endforeach

        <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
            <button type="submit" class="btn-cms btn-cms-primary">✓ Save Templates</button>
            <a href="{{ route('admin.crud.index', $table) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script src="{{ route('admin.page-asset', ['crud/script.js']) }}" defer></script>
@endpush

