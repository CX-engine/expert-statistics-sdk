{{-- The host's page shell; see components/page.blade.php for why this is a separate view. --}}
<x-pages.index :title="$title" :subtitle="$subtitle">
    {{ $content }}
</x-pages.index>
