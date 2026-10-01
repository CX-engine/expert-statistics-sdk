{{--
    Drill-down link from a stat to the call analysis page (see CallAnalysisLink).
    Inline by default; `tile` renders a block card that keeps its box (as a <div>) when not linked.
    Without $href (route not registered by the host app) the stat renders unlinked.
--}}
@props(['href' => null, 'tile' => false])
@if ($href)
<a href="{{ $href }}" target="_blank" {{ $attributes->class([$tile ? 'block hover:ring-2 transition-all' : 'hover:underline', 'cursor-pointer']) }}>{{ $slot }}</a>
@elseif ($tile)
<div {{ $attributes }}>{{ $slot }}</div>
@else
{{ $slot }}
@endif
