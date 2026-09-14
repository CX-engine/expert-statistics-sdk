@if ($embedded)
    @include('expert-statistics::livewire.configuration._manage-pbx-settings-content')
@else
    <x-pages.index :title="__('expert-statistics::pbx.navigation.configuration')">
        @include('expert-statistics::livewire.configuration._manage-pbx-settings-content')
    </x-pages.index>
@endif
