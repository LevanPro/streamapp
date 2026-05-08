@php
    $ext = strtolower($resource->extension ?? pathinfo($resource->filename, PATHINFO_EXTENSION));
    $isPdf = $ext === 'pdf';
    $isTextPreview = in_array($ext, ['txt', 'go'], true);
@endphp

<div style="display:flex; justify-content:space-between; align-items:center; gap:10px; border:1px solid var(--line); border-radius:10px; padding:8px 10px;">
    <span class="muted" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $resource->display_title }}</span>

    <div style="display:flex; align-items:center; gap:10px; flex-shrink:0;">
        @if($isTextPreview)
            <button
                type="button"
                class="btn btn-outline js-resource-preview"
                data-preview-url="{{ route('resources.preview', $resource) }}"
                data-resource-title="{{ $resource->display_title }}"
                style="padding:5px 9px; font-size:0.82rem;"
            >View</button>
            <a href="{{ route('stream.resources', $resource) }}" class="muted" style="font-size:0.82rem;">Download</a>
        @elseif($isPdf)
            <a href="{{ route('stream.resources', $resource) }}" target="_blank" rel="noopener" class="muted" style="font-size:0.82rem;">Open PDF</a>
        @else
            <a href="{{ route('stream.resources', $resource) }}" class="muted" style="font-size:0.82rem;">Download</a>
        @endif
        <span class="muted" style="font-size:0.78rem;">{{ number_format($resource->file_size_bytes / 1048576, 2) }} MB</span>
    </div>
</div>
