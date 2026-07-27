{{-- iClinicSys checkbox / option mark (read-only display) --}}
@php
    $checked = $checked ?? false;
    $label = $label ?? '';
    $subLabel = $subLabel ?? null;
    $inline = $inline ?? true;
@endphp
<span @class(['mark', 'mark-block' => ! $inline])>
    <span class="mark-box">{!! $checked ? 'X' : '&#160;' !!}</span>
    @if ($label !== '')
        <span class="mark-label">
            {!! $label !!}
            @if ($subLabel)
                <span class="sub-label" style="display:block;">{{ $subLabel }}</span>
            @endif
        </span>
    @endif
</span>
