@props(['title' => null, 'subtitle' => null])

{{--
    Shared page shell for every Expert Statistics page, matching Filament's
    cluster sub-navigation pattern (a secondary nav listing the cluster's
    own pages, rendered alongside the page content - see
    https://filamentphp.com/docs/navigation/clusters, "start" position).
--}}
<x-expert-statistics::page :title="$title" :subtitle="$subtitle">
    @if (config('expert-statistics-api.page_shell', true))
        <div class="lg:flex lg:items-start lg:gap-8">
            @include('expert-statistics::components.cluster-navigation')

            <div class="min-w-0 flex-1">
                {{ $slot }}
            </div>
        </div>
    @else
        {{ $slot }}
    @endif

    @if (config('expert-statistics-api.docs_panel_enabled', true))
        <livewire:expert-statistics.docs.helper-panel :key="'expert-statistics.docs.helper-panel'" />
    @endif
    @if (config('expert-statistics-api.docs_assistant.enabled', false))
        <livewire:expert-statistics.docs.assistant-chat :key="'expert-statistics.docs.assistant-chat'" />
    @endif
</x-expert-statistics::page>
