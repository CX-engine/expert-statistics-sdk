{{-- Previous / next for a paginated config-audit list ($meta = {total, page, per_page, last_page}). --}}
@if (($meta['last_page'] ?? 1) > 1)
    <div class="flex items-center justify-between pt-3 text-xs text-gray-500 dark:text-gray-400">
        <span>{{ $t('page_of', ['page' => $meta['page'], 'last' => $meta['last_page'], 'total' => $meta['total']]) }}</span>
        <div class="flex gap-2">
            <button wire:click="goToPage('{{ $list }}', {{ $meta['page'] - 1 }})" @disabled($meta['page'] <= 1)
                    class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-40">{{ $t('previous') }}</button>
            <button wire:click="goToPage('{{ $list }}', {{ $meta['page'] + 1 }})" @disabled($meta['page'] >= $meta['last_page'])
                    class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-40">{{ $t('next') }}</button>
        </div>
    </div>
@endif
