@php
    $t = fn (string $key, array $replace = []) => __('expert-statistics::pbx.config.auditFiles.'.$key, $replace);
    $canModify = $this->canModify();
    $sources = collect($files['call_flows'] ?? [])->keyBy('app');
    $input = 'block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 dark:file:bg-gray-700 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 dark:file:text-gray-200 hover:file:bg-gray-200 dark:hover:file:bg-gray-600';
    $button = 'inline-flex items-center rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50';
@endphp

<div class="space-y-8">
    @assets
        <script>{!! \CXEngine\ExpertStatistics\Support\BackupReaderScript::source() !!}</script>
    @endassets

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
        {{--
            3CX backup import. The backup is never sent: backup-reader.js reads it on the user's
            computer and keeps only the configuration XML (credentials blanked) and the audio
            prompts, which are then sent with $wire.upload() (prompts in batches under the upload
            limit). Never put wire:model on these inputs: it would upload the whole backup.
        --}}
        @if ($canModify)
            @php $limits = $this->readerLimits(); @endphp
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-4">
                <div>
                    <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">{{ $t('importSection') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('importHelp') }}</p>
                </div>
                <div
                    class="md:col-span-2 space-y-3"
                    x-data="{
                        dragging: false,
                        busy: false,
                        step: null,
                        percent: 0,
                        error: null,
                        summary: null,
                        texts: @js(__('expert-statistics::pbx.config.auditFiles.reader')),
                        errors: @js(__('expert-statistics::pbx.config.auditFiles.errors')),
                        limits: @js($limits),
                        locale: @js(str_replace('_', '-', app()->getLocale())),
                        message(code) {
                            return (this.errors[code] ?? this.errors.generic).replace(':max', this.limits.maxXmlMb);
                        },
                        dropped(event) {
                            this.dragging = false;
                            const item = event.dataTransfer?.items?.[0];
                            const entry = item?.webkitGetAsEntry ? item.webkitGetAsEntry() : null;
                            this.take(entry?.isDirectory ? entry : event.dataTransfer?.files?.[0]);
                        },
                        send(name, file) {
                            return new Promise((resolve, reject) => this.$wire.upload(name, file, resolve, reject, (e) => { this.percent = e.detail.progress }));
                        },
                        async take(input) {
                            if (this.busy) return;
                            this.error = null;
                            this.summary = null;
                            if (! window.expertStatsBackupReader) { this.error = this.message('unsupported'); return; }
                            this.busy = true;
                            try {
                                let read;
                                try {
                                    read = await window.expertStatsBackupReader.read(input ?? [], {
                                        batchBytes: this.limits.batchBytes,
                                        maxXmlBytes: this.limits.maxXmlBytes,
                                        onProgress: (p) => { this.step = p.step; this.percent = p.percent },
                                    });
                                } catch (e) {
                                    console.error(e);
                                    this.error = this.message(e?.code);
                                    return;
                                }
                                this.summary = read.summary;
                                // Prompts first (transcribed after each batch), then the configuration (the audit is re-run).
                                for (let i = 0; i < read.batches.length; i++) {
                                    this.step = 'sending_prompts';
                                    this.texts.current = (i + 1) + '/' + read.batches.length;
                                    this.percent = 0;
                                    try { await this.send('promptsFile', read.batches[i]); } catch { this.error = this.message('upload_failed'); return; }
                                    await this.$wire.uploadPrompts();
                                    if (this.$wire.statusType === 'error') return;
                                }
                                this.step = 'sending_config';
                                this.percent = 0;
                                try { await this.send('backupFile', read.xml); } catch { this.error = this.message('upload_failed'); return; }
                                await this.$wire.uploadBackup();
                                if (this.$wire.statusType === 'error') return;
                                await this.$wire.backupImported(read.summary.prompts);
                            } finally {
                                this.busy = false;
                                this.step = null;
                            }
                        },
                        stepText() {
                            return (this.texts.steps[this.step] ?? '').replace(':current', this.texts.current ?? '');
                        },
                        size(bytes) {
                            return bytes >= 1073741824
                                ? (bytes / 1073741824).toLocaleString(this.locale, { maximumFractionDigits: 1 }) + ' ' + this.texts.units.gb
                                : (bytes / 1048576).toLocaleString(this.locale, { maximumFractionDigits: 1 }) + ' ' + this.texts.units.mb;
                        },
                        summaryText() {
                            const s = this.summary;
                            return (s.sourceBytes ? this.texts.summary : this.texts.summary_folder)
                                .replace(':read', this.size(s.readBytes))
                                .replace(':source', this.size(s.sourceBytes ?? 0))
                                .replace(':prompts', s.prompts)
                                .replace(':redacted', s.redacted);
                        },
                    }"
                >
                    <label
                        class="relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-6 py-8 text-center transition"
                        x-bind:class="dragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30' : 'border-gray-300 dark:border-gray-600 hover:border-primary-400'"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave="dragging = false"
                        x-on:drop.prevent="dropped($event)"
                    >
                        <input
                            type="file"
                            accept=".zip,.xml,application/zip,application/xml,text/xml"
                            x-on:change="take($event.target.files[0]); $event.target.value = ''"
                            x-bind:disabled="busy"
                            class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                            aria-label="{{ $t('dropTitle') }}"
                        >
                        <svg class="h-10 w-10 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" /></svg>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('dropTitle') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('dropHint') }}</p>

                        <div x-show="busy" x-cloak class="w-full max-w-md pt-2">
                            <div class="h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-full rounded-full bg-primary-600 transition-all" x-bind:style="'width: ' + percent + '%'"></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><span x-text="stepText()"></span> <span x-text="percent + '%'"></span></p>
                        </div>
                    </label>

                    <div class="flex flex-col items-center gap-1 text-center">
                        <input type="file" webkitdirectory directory multiple x-ref="folder" class="hidden"
                            x-on:change="take([...$event.target.files]); $event.target.value = ''">
                        <button type="button" x-on:click="$refs.folder.click()" x-bind:disabled="busy"
                            class="text-sm font-medium text-primary-600 hover:underline disabled:opacity-50">{{ $t('chooseFolder') }}</button>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('folderHint') }}</p>
                    </div>

                    <p x-show="summary" x-cloak class="text-xs text-emerald-700 dark:text-emerald-400" x-text="summary ? summaryText() : ''"></p>
                    <p x-show="error" x-cloak class="text-sm text-red-600" x-text="error"></p>
                    @error('backupFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('promptsFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

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
            </div>
        </div>

        {{-- Call Flow sources: hidden for now (config expert-statistics-api.config_audit.call_flow_upload) --}}
        @if ($this->callFlowUploadEnabled())
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
        @endif

        {{-- Audio prompts --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-4">
            <div>
                <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">{{ $t('promptsSection') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('promptsHelp') }}</p>
            </div>
            <div class="md:col-span-2 space-y-3">
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $t('promptsCount', ['count' => $files['prompts']['count'] ?? 0, 'transcribed' => $files['prompts']['transcribed'] ?? 0]) }}</p>
            </div>
        </div>

        {{-- How to get the backup / what happens to the data --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer text-sm font-semibold text-gray-900 dark:text-white">{{ $t('help.title') }}</summary>
                <div class="mt-3 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                    <p>{{ $t('help.intro') }}</p>
                    @foreach (['light', 'existing', 'folder'] as $way)
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ $t("help.{$way}.title") }}</h3>
                            <ol class="mt-1 list-decimal space-y-1 pl-5">
                                @foreach (__("expert-statistics::pbx.config.auditFiles.help.{$way}.steps") as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                    <p class="text-xs text-gray-400">{{ $t('help.note') }}</p>
                </div>
            </details>
            <details class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <summary class="cursor-pointer text-sm font-semibold text-gray-900 dark:text-white">{{ $t('privacy.title') }}</summary>
                <dl class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    @foreach (__('expert-statistics::pbx.config.auditFiles.privacy.points') as $point)
                        <div>
                            <dt class="font-semibold text-gray-800 dark:text-gray-100">{{ $point['title'] }}</dt>
                            <dd>{{ $point['text'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </details>
        </div>
    @endif
</div>
