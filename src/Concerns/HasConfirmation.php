<?php

namespace CXEngine\ExpertStatistics\Concerns;

/**
 * Generic "are you sure?" confirmation flow for a Livewire component, ported
 * from bluerocktelclients' App\Livewire\Concerns\HasConfirmation so the ported
 * report pages use the same custom modal UI instead of the browser's native
 * wire:confirm dialog. Pair with <x-expert-statistics::confirm-modal />.
 */
trait HasConfirmation
{
    /** @var array<string, mixed>|null */
    public ?array $confirm = null;

    /**
     * @param  array<int, mixed>  $args
     */
    public function askConfirm(
        string $title,
        string $body,
        string $method,
        array $args = [],
        bool $danger = true,
        string $confirmLabel = '',
    ): void {
        $this->confirm = [
            'title' => $title,
            'body' => $body,
            'method' => $method,
            'args' => $args,
            'danger' => $danger,
            'confirmLabel' => $confirmLabel ?: __('expert-statistics::pbx.reports.delete'),
        ];
    }

    public function executeConfirmed(): void
    {
        if (empty($this->confirm['method'])) {
            return;
        }

        $method = $this->confirm['method'];
        $args = $this->confirm['args'] ?? [];
        $this->confirm = null;

        if (! method_exists($this, $method) || str_starts_with($method, '_')) {
            return;
        }

        $this->{$method}(...$args);
    }

    public function cancelConfirm(): void
    {
        $this->confirm = null;
    }
}
