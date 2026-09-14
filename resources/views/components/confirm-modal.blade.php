<div
    x-cloak
    x-show="$wire.confirm"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    @keydown.escape.window="$wire.cancelConfirm()"
>
    {{-- Backdrop --}}
    <div
        x-show="$wire.confirm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-gray-950/50 backdrop-blur-sm dark:bg-gray-950/70"
        @click="$wire.cancelConfirm()"
        aria-hidden="true"
    ></div>

    {{-- Panel --}}
    <div
        x-show="$wire.confirm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="relative flex w-full max-w-sm flex-col items-center rounded-2xl bg-white p-6 text-center shadow-2xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        role="dialog"
        aria-modal="true"
        @click.stop
    >
        {{-- Icon --}}
        <div
            class="mb-4 flex h-12 w-12 shrink-0 items-center justify-center rounded-full"
            :class="$wire.confirm?.danger !== false
                ? 'bg-red-50 dark:bg-red-950/30'
                : 'bg-amber-50 dark:bg-amber-950/30'"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6"
                :class="$wire.confirm?.danger !== false
                    ? 'text-red-600 dark:text-red-400'
                    : 'text-amber-600 dark:text-amber-400'"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>

        {{-- Title --}}
        <h2
            class="text-base font-semibold text-gray-900 dark:text-white"
            x-text="$wire.confirm?.title"
        ></h2>

        {{-- Body --}}
        <p
            x-show="$wire.confirm?.body"
            class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400"
            x-text="$wire.confirm?.body"
        ></p>

        {{-- Actions --}}
        <div class="mt-6 flex w-full items-center gap-3">
            <button
                type="button"
                @click="$wire.cancelConfirm()"
                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                {{ __('expert-statistics::pbx.reports.editCancel') }}
            </button>
            <button
                type="button"
                @click="$wire.executeConfirmed()"
                class="flex-1 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition focus:outline-none focus-visible:ring-2"
                :class="$wire.confirm?.danger !== false
                    ? 'bg-red-600 hover:bg-red-500 focus-visible:ring-red-500 dark:bg-red-700 dark:hover:bg-red-600'
                    : 'bg-amber-500 hover:bg-amber-400 focus-visible:ring-amber-400 dark:bg-amber-600 dark:hover:bg-amber-500'"
                x-text="$wire.confirm?.confirmLabel"
            ></button>
        </div>
    </div>
</div>
