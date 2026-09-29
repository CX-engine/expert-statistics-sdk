{{-- Stand-in for the host app's <x-pages.index> page shell (tests only). --}}
@props(['title' => null, 'subtitle' => null])
<div><h1>{{ $title }}</h1><p>{{ $subtitle }}</p>{{ $slot }}</div>
