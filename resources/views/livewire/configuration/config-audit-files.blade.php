@php
    $t = fn (string $key, array $replace = []) => __('expert-statistics::pbx.config.auditFiles.'.$key, $replace);
    $canModify = $this->canModify();
    $sources = collect($files['call_flows'] ?? [])->keyBy('app');
    $input = 'block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 dark:file:bg-gray-700 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 dark:file:text-gray-200 hover:file:bg-gray-200 dark:hover:file:bg-gray-600';
    $button = 'inline-flex items-center rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50';
@endphp

<div class="space-y-8">
    @if ($statusMessage)
        <div @class([
            'rounded-xl border px-4 py-3 text-sm',
            'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-400' => $statusType === 'success',
            'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-900 text-red-700 dark:text-red-400' => $statusType === 'error',
        ])>{{ $statusMessage }}</div>
    @endif

    @if ($state === 'not_activated')
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config_audit.not_activated') }}</p>
    @elseif ($state === 'error')
        <p class="text-sm text-red-600">{{ __('expert-statistics::pbx.expert_statistics.error_loading') }}</p>
    @else
        {{-- Backup configuration --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-4">
            <div>
                <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">{{ $t('backupSection') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('backupHelp') }}</p>
            </div>
            <div class="md:col-span-2 space-y-3">
                <ul class="divide-y divide-gray-100 dark:divide-gray-700 rounded-xl border border-gray-200 dark:border-gray-700 text-sm">
                    @forelse (array_slice($files['backups'] ?? [], 0, 5) as $backup)
                        <li class="flex items-center justify-between px-4 py-2">
                            <span class="text-gray-800 dark:text-gray-200">{{ $backup['backup_date'] ? \Carbon\Carbon::parse($backup['backup_date'])->isoFormat('L LT') : $backup['file'] }}</span>
                            <span class="text-xs text-gray-400">{{ $backup['uploaded'] ? $t('uploaded') : $t('collected') }} · {{ number_format($backup['size'] / 1048576, 1) }} MB</span>
                        </li>
                    @empty
                        <li class="px-4 py-3 text-gray-500">{{ $t('noBackup') }}</li>
                    @endforelse
                </ul>
                @if ($canModify)
                    <form wire:submit="uploadBackup" class="flex flex-wrap items-center gap-3">
                        <input type="file" wire:model="backupFile" accept=".xml" class="{{ $input }} flex-1 min-w-60" />
                        <button type="submit" class="{{ $button }}" wire:loading.attr="disabled" wire:target="backupFile, uploadBackup">{{ $t('uploadBackup') }}</button>
                    </form>
                    @error('backupFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @endif
            </div>
        </div>

        {{-- Call Flow sources --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-4">
            <div>
                <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">{{ $t('callFlowSection') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('callFlowHelp') }}</p>
            </div>
            <div class="md:col-span-2 space-y-3">
                <ul class="divide-y divide-gray-100 dark:divide-gray-700 rounded-xl border border-gray-200 dark:border-gray-700 text-sm">
                    @forelse ($files['call_flow_apps'] ?? [] as $app)
                        @php $source = $sources[$app['app']] ?? null; @endphp
                        <li wire:key="cfd-{{ $app['app'] }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-2">
                            <span class="text-gray-800 dark:text-gray-200">{{ $app['name'] }}</span>
                            @if ($source)
                                <span class="text-xs text-emerald-600 dark:text-emerald-400">
                                    {{ $t('sourcesProvided', ['date' => \Carbon\Carbon::parse($source['uploaded_at'])->isoFormat('L'), 'name' => $source['uploaded_by'] ?? '—']) }}
                                    @if ($canModify)
                                        · <button type="button" wire:click="deleteCallFlow(@js($app['app']))" wire:confirm="{{ $t('confirmDelete') }}" class="text-red-600 hover:underline">{{ $t('delete') }}</button>
                                    @endif
                                </span>
                            @else
                                <span class="text-xs text-amber-600 dark:text-amber-400">{{ $t('sourcesMissing') }}</span>
                            @endif
                        </li>
                    @empty
                        <li class="px-4 py-3 text-gray-500">{{ $t('noCallFlowApp') }}</li>
                    @endforelse
                </ul>
                @if ($canModify && ! empty($files['call_flow_apps']))
                    <form wire:submit="uploadCallFlow" class="flex flex-wrap items-center gap-3">
                        <select wire:model="callFlowApp" class="rounded-lg border-gray-300 dark:border-gray-600 bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100 dark:[color-scheme:dark] text-sm">
                            <option value="">{{ $t('chooseApp') }}</option>
                            @foreach ($files['call_flow_apps'] as $app)
                                <option value="{{ $app['app'] }}">{{ $app['name'] }}</option>
                            @endforeach
                        </select>
                        <input type="file" wire:model="callFlowFile" accept=".zip" class="{{ $input }} flex-1 min-w-60" />
                        <button type="submit" class="{{ $button }}" wire:loading.attr="disabled" wire:target="callFlowFile, uploadCallFlow">{{ $t('uploadCallFlow') }}</button>
                    </form>
                    @error('callFlowApp') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('callFlowFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @endif
            </div>
        </div>

        {{-- Audio prompts --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-4">
            <div>
                <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">{{ $t('promptsSection') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('promptsHelp') }}</p>
            </div>
            <div class="md:col-span-2 space-y-3">
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $t('promptsCount', ['count' => $files['prompts']['count'] ?? 0, 'transcribed' => $files['prompts']['transcribed'] ?? 0]) }}</p>
                @if ($canModify)
                    <form wire:submit="uploadPrompts" class="flex flex-wrap items-center gap-3">
                        <input type="file" wire:model="promptsFile" accept=".zip" class="{{ $input }} flex-1 min-w-60" />
                        <button type="submit" class="{{ $button }}" wire:loading.attr="disabled" wire:target="promptsFile, uploadPrompts">{{ $t('uploadPrompts') }}</button>
                    </form>
                    @error('promptsFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @endif
            </div>
        </div>
    @endif
</div>
