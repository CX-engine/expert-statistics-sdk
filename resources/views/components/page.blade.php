@props(['title' => null, 'subtitle' => null])

{{--
    Page wrapper for every full-page component. With `page_shell` enabled
    (the default) it renders inside the host's <x-pages.index> (title,
    subtitle, host chrome); disabled, it renders the content alone, for a
    host that wraps these components in its own pages (e.g. Filament).
    The host tag lives in its own view, included only when the shell is
    on: Blade resolves component tags when it compiles a view, so a host
    without a `pages.index` component must never compile one that has it.

    The root <div> must stay outside the @if: Livewire wraps conditionals
    in <!--[if BLOCK]> morph markers, and markers ahead of a component's
    root element make Livewire attach wire:id to the wrong element, which
    breaks every later update ("Snapshot missing on Livewire component").
--}}
<div>
    @if (config('expert-statistics-api.page_shell', true))
        @include('expert-statistics::components.host-page', ['title' => $title, 'subtitle' => $subtitle, 'content' => $slot])
    @else
        {{ $slot }}
    @endif
</div>
