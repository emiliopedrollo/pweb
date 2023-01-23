@php
$classes = ($classes ?? '') . (($active ?? false) ? ' nav-link active' : ' nav-link');
@endphp
<li class="nav-item">
    <a {!! $attributes ?? '' !!} class="{{ $classes }}" href="{{ $href }}">
        {{ $slot }}
    </a>
</li>
