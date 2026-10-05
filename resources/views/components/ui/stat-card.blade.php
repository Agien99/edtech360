@props(['label', 'value', 'meta' => null, 'icon' => 'bi-graph-up', 'tone' => 'blue'])
<div class="stat-card">
    <div class="stat-icon tone-{{ $tone }}"><i class="bi {{ $icon }}"></i></div>
    <div class="stat-copy">
        <span>{{ $label }}</span>
        <strong>{{ $value }}</strong>
        @if($meta)<small>{{ $meta }}</small>@endif
    </div>
</div>
