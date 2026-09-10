<?php

namespace CXEngine\ExpertStatistics\Livewire\CallAnalysis;

use Carbon\Carbon;
use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\HasPbxElementSelector;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use CXEngine\ExpertStatistics\Support\PbxDataProcessor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * "Call Flow Analysis" / Call Details page, ported byte-for-byte (business
 * logic) from bluerocktelclients' App\Filament\Pages\ExpertStatistics\CallAnalysisPage.
 * Plain Livewire full-page component: no Filament page. Filters a paginated
 * CDR (call detail record) browser via ExpertStatisticsService::getCdrReport(),
 * and drills into each call's segment flow using the already-ported
 * PbxDataProcessor helpers.
 *
 * Unlike every other report page in this package, this page filters on FOUR
 * independent element dimensions at once (origin DN / destination DN / DID
 * number / caller number), each with its own selected-list and (for origin
 * and destination) a type toggle. HasPbxElementSelector's single
 * dn/selectedElements/pbxElements model — built for the single-selector My
 * Queues/My Users report pages — doesn't fit that shape, so it isn't used
 * for element selection here. HasPbxElementSelector is still included for
 * the $showDatePicker property and the PersistsExpertStatisticsFilters trait
 * it pulls in (period/time persistence); this class defines its own
 * applyFilters(), updateTimeRange() and setCustomRange(), which shadow the
 * trait's same-named methods, matching the original CallAnalysisPage's own
 * bespoke multi-selector logic (ported as-is below, just re-pointed at
 * ExpertStatisticsService instead of PbxApiService).
 */
class CallAnalysis extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use HasPbxElementSelector;

    #[Url]
    public string $selectedPeriod = 'lastMonth';

    #[Url]
    public ?string $startDate = null;

    #[Url]
    public ?string $endDate = null;

    #[Url]
    public string $startTime = '00:00';

    #[Url]
    public string $endTime = '23:59';

    #[Url]
    public string $originDn = '';

    #[Url]
    public string $originDnType = '';

    #[Url]
    public string $destinationDn = '';

    #[Url]
    public string $destinationDnType = '';

    #[Url]
    public string $didNumber = '';

    #[Url]
    public string $callerNumber = '';

    #[Url]
    public string $callWay = '';

    #[Url]
    public string $callStatus = 'all';

    #[Url]
    public bool $excludeClosedHours = false;

    /** @var string[] */
    public array $selectedOriginDns = [];

    /** @var string[] */
    public array $selectedDestinationDns = [];

    /** @var string[] */
    public array $selectedDidNumbers = [];

    /** @var string[] */
    public array $selectedCallerNumbers = [];

    /** @var array<int, array<string, mixed>> */
    public array $calls = [];

    /** @var array{current_page: int, per_page: int, total: int, last_page: int} */
    public array $pagination = [
        'current_page' => 1,
        'per_page' => 50,
        'total' => 0,
        'last_page' => 1,
    ];

    /** @var array<int, array<string, mixed>> */
    public array $extensionsReduced = [];

    /** @var array<int, array<string, mixed>> */
    public array $queuesReduced = [];

    /** @var array<int, array<string, mixed>> */
    public array $didsReduced = [];

    /** @var array<string, mixed> */
    public array $pbxMap = [];

    /** @var array<string, string> Maps caller number => resource group name (type 99) */
    public array $callerGroupMap = [];

    /** @var array<int, array<string, mixed>> Caller resource groups for the filter selector */
    public array $callersReduced = [];

    public bool $filtersApplied = false;

    public bool $isLoading = false;

    public string $callerSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $callerSearchResults = [];

    public function mount(): void
    {
        $this->restoreExpertStatsFilters();
        $this->applyPeriod($this->selectedPeriod);

        if ($this->originDn) {
            $this->selectedOriginDns = array_values(array_filter(explode(',', $this->originDn)));
        }
        if ($this->destinationDn) {
            $this->selectedDestinationDns = array_values(array_filter(explode(',', $this->destinationDn)));
        }
        if ($this->didNumber) {
            $this->selectedDidNumbers = array_values(array_filter(explode(',', $this->didNumber)));
        }
        if ($this->callerNumber) {
            $this->selectedCallerNumbers = array_values(array_filter(explode(',', $this->callerNumber)));
        }

        $this->loadElementLists();
        $this->callerSearchResults = $this->callersReduced;

        if ($this->originDn || $this->destinationDn || $this->didNumber || $this->callerNumber) {
            $this->filtersApplied = true;
            $this->loadData();
        }
    }

    public function selectPeriod(string $value): void
    {
        $this->selectedPeriod = $value;
        $this->showDatePicker = false;
        $this->applyPeriod($value);
        $this->saveExpertStatsPeriod();
    }

    public function setCustomRange(string $start, string $end): void
    {
        $this->startDate = $start;
        $this->endDate = $end;
        $this->selectedPeriod = 'custom';
        $this->showDatePicker = false;
        $this->saveExpertStatsPeriod();
    }

    public function updateTimeRange(): void
    {
        $this->saveExpertStatsTime();
    }

    public function updatedOriginDnType(): void
    {
        $this->selectedOriginDns = [];
        $this->originDn = '';
    }

    public function updatedDestinationDnType(): void
    {
        $this->selectedDestinationDns = [];
        $this->destinationDn = '';
    }

    public function updatedCallerSearch(string $value): void
    {
        if (\strlen($value) >= 2) {
            $this->doSearchCallerNumbers();
        } else {
            $this->callerSearchResults = $this->callersReduced;
        }
    }

    /** @param  array{label: string, value: string|string[]}  $element */
    public function addOriginDn(array $element): void
    {
        $this->toggleFilterSelection($element, $this->selectedOriginDns, 'originDn');
    }

    /** @param  array{label: string, value: string|string[]}  $element */
    public function addDestinationDn(array $element): void
    {
        $this->toggleFilterSelection($element, $this->selectedDestinationDns, 'destinationDn');
    }

    /** @param  array{label: string, value: string|string[]}  $element */
    public function addDidNumber(array $element): void
    {
        $this->toggleFilterSelection($element, $this->selectedDidNumbers, 'didNumber');
    }

    /** @param  array{label: string, value: string|string[]}  $element */
    public function addCallerNumber(array $element): void
    {
        $this->toggleFilterSelection($element, $this->selectedCallerNumbers, 'callerNumber');
    }

    public function elementRemovedOrigin(string $label): void
    {
        $value = $this->normalizeValue($label);
        $this->selectedOriginDns = array_values(array_filter(
            $this->selectedOriginDns,
            fn (string $v): bool => $v !== $value,
        ));
        $this->originDn = implode(',', $this->selectedOriginDns);
    }

    public function elementRemovedDestination(string $label): void
    {
        $value = $this->normalizeValue($label);
        $this->selectedDestinationDns = array_values(array_filter(
            $this->selectedDestinationDns,
            fn (string $v): bool => $v !== $value,
        ));
        $this->destinationDn = implode(',', $this->selectedDestinationDns);
    }

    public function elementRemovedDid(string $label): void
    {
        $value = $this->normalizeValue($label);
        $this->selectedDidNumbers = array_values(array_filter(
            $this->selectedDidNumbers,
            fn (string $v): bool => $v !== $value,
        ));
        $this->didNumber = implode(',', $this->selectedDidNumbers);
    }

    public function elementRemovedCaller(string $label): void
    {
        $value = $this->normalizeValue($label);
        $this->selectedCallerNumbers = array_values(array_filter(
            $this->selectedCallerNumbers,
            fn (string $v): bool => $v !== $value,
        ));
        $this->callerNumber = implode(',', $this->selectedCallerNumbers);
    }

    public function applyFilters(): void
    {
        $this->pagination['current_page'] = 1;
        $this->filtersApplied = true;
        $this->loadData();
    }

    /** Called by Livewire after wire:model updates excludeClosedHours. */
    public function updatedExcludeClosedHours(): void
    {
        $this->pagination['current_page'] = 1;
        $this->loadData();
    }

    public function resetFilters(): void
    {
        $this->selectedOriginDns = [];
        $this->selectedDestinationDns = [];
        $this->selectedDidNumbers = [];
        $this->selectedCallerNumbers = [];

        $this->originDn = '';
        $this->originDnType = '';
        $this->destinationDn = '';
        $this->destinationDnType = '';
        $this->didNumber = '';
        $this->callerNumber = '';
        $this->callWay = '';
        $this->callStatus = 'all';
        $this->startTime = '00:00';
        $this->endTime = '23:59';
        $this->callerSearch = '';
        $this->callerSearchResults = $this->callersReduced;

        $this->calls = [];
        $this->filtersApplied = false;
        $this->pagination = ['current_page' => 1, 'per_page' => 50, 'total' => 0, 'last_page' => 1];
    }

    public function changePage(int $page): void
    {
        $this->pagination['current_page'] = $page;
        $this->loadData();
    }

    /**
     * Points at CdrExportController's route, wired by a later phase
     * (modules/ExpertStatistics/Routes/tenant.php). Falls back to '#' while
     * that route doesn't exist yet, the same Route::has() guard used by
     * ManagePbxSettings::shareUrl() for the public wallboard link.
     */
    public function getExportUrl(): string
    {
        if (! $this->startDate || ! $this->endDate || ! Route::has('expert-stats.call-details.export')) {
            return '#';
        }

        $params = array_filter([
            'start_date' => str_replace('-', '', $this->startDate),
            'end_date' => str_replace('-', '', $this->endDate),
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'origin_dn' => $this->originDn,
            'origin_dn_type' => $this->originDnType,
            'destination_dn' => $this->destinationDn,
            'destination_dn_type' => $this->destinationDnType,
            'did_number' => $this->didNumber,
            'caller_number' => $this->callerNumber,
            'call_way' => $this->callWay,
            'call_status' => $this->callStatus !== 'all' ? $this->callStatus : '',
            'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
        ]);

        return route('expert-stats.call-details.export', $params);
    }

    public function formatDuration(string $startedAt, string $endedAt): string
    {
        return PbxDataProcessor::formatDuration($startedAt, $endedAt);
    }

    /**
     * @param  array<int, array<string, mixed>>  $flow
     * @return array<int, array<string, mixed>>
     */
    public function buildFlowPreview(array $flow): array
    {
        return PbxDataProcessor::buildFlowPreview($flow);
    }

    public function resolveStepName(string $dn, string $type, ?string $name): string
    {
        return PbxDataProcessor::resolveStepName($dn, $type, $name, $this->pbxMap);
    }

    /** Returns a translation KEY (e.g. 'expert-statistics::pbx.expert_statistics.cfa_segment_talk') - translate with __() in the view. */
    public function getSegmentLabel(string $segmentType, bool $answered): string
    {
        return PbxDataProcessor::getSegmentLabel($segmentType, $answered);
    }

    public function formatSecondsShort(int $secs): string
    {
        return PbxDataProcessor::formatSecondsShort($secs);
    }

    public function resolveDisplayName(string $dn, string $type): string
    {
        return PbxDataProcessor::resolveDisplayName($dn, $type, $this->pbxMap);
    }

    public function loadData(): void
    {
        if (! $this->startDate || ! $this->endDate) {
            return;
        }

        $this->isLoading = true;

        try {
            $params = array_filter([
                'start_date' => str_replace('-', '', $this->startDate),
                'end_date' => str_replace('-', '', $this->endDate),
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'origin_dn' => $this->originDn,
                'origin_dn_type' => $this->originDnType,
                'destination_dn' => $this->destinationDn,
                'destination_dn_type' => $this->destinationDnType,
                'did_number' => $this->didNumber,
                'caller_number' => $this->callerNumber,
                'call_way' => $this->callWay,
                'call_status' => $this->callStatus !== 'all' ? $this->callStatus : '',
                'page' => $this->pagination['current_page'],
                'per_page' => $this->pagination['per_page'],
                'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
            ]);

            $raw = app(ExpertStatisticsService::class)->getCdrReport($params);

            $this->calls = $raw['data'] ?? [];
            $this->pagination = [
                'current_page' => (int) ($raw['current_page'] ?? 1),
                'per_page' => (int) ($raw['per_page'] ?? 50),
                'total' => (int) ($raw['total'] ?? 0),
                'last_page' => (int) ($raw['last_page'] ?? 1),
            ];
        } catch (\Throwable $e) {
            report($e);
            $this->calls = [];
        }

        $this->isLoading = false;
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.call-analysis.index');
    }

    private function doSearchCallerNumbers(): void
    {
        try {
            $raw = app(ExpertStatisticsService::class)->searchCallerNumbers($this->callerSearch);
            $items = $raw['data'] ?? (array) $raw;
            $this->callerSearchResults = array_map(fn (mixed $item): array => [
                'value' => \is_array($item) ? ($item['caller_number'] ?? $item['value'] ?? '') : (string) $item,
                'label' => \is_array($item) ? ($item['caller_number'] ?? $item['label'] ?? '') : (string) $item,
                'isConstructor' => false,
                'group' => false,
            ], $items);
        } catch (\Throwable $e) {
            report($e);
            $this->callerSearchResults = $this->callersReduced;
        }
    }

    private function loadElementLists(): void
    {
        $service = app(ExpertStatisticsService::class);

        try {
            $resourceGroups = $service->getResourceGroups();
            $map = $service->getMap();

            $this->pbxMap = $map;

            foreach (collect($resourceGroups)->filter(fn (array $g): bool => ($g['type'] ?? null) == 99)->values() as $group) {
                foreach (json_decode($group['resources'] ?? '[]', true) ?? [] as $number) {
                    $this->callerGroupMap[(string) $number] ??= $group['name'];
                }
            }

            $typeConfig = [
                0 => [
                    'key' => 'extensionsReduced',
                    'elements' => collect($map['extensions'] ?? [])
                        ->map(fn (string $name, string $dn): array => [
                            'value' => $dn,
                            'label' => $dn.' — '.$name,
                            'group' => false,
                        ])->values()->all(),
                ],
                4 => [
                    'key' => 'queuesReduced',
                    'elements' => collect($map['call_queues'] ?? [])
                        ->map(fn (mixed $q, string $dn): array => [
                            'value' => $dn,
                            'label' => $dn.' — '.(is_array($q) ? ($q['name'] ?? $dn) : $dn),
                            'group' => false,
                        ])->values()->all(),
                ],
                1 => [
                    'key' => 'didsReduced',
                    'elements' => collect($map['dids'] ?? [])
                        ->map(fn (mixed $v): array => [
                            'value' => (string) (is_array($v) ? ($v['number'] ?? $v) : $v),
                            'label' => (string) (is_array($v) ? ($v['number'] ?? $v) : $v),
                            'group' => false,
                        ])->unique('value')->values()->all(),
                ],
            ];

            $this->callersReduced = collect($resourceGroups)
                ->filter(fn (array $g): bool => ($g['type'] ?? null) == 99)
                ->map(fn (array $group): array => [
                    'value' => array_map('strval', json_decode($group['resources'] ?? '[]', true) ?? []),
                    'label' => $group['name'],
                    'isConstructor' => true,
                    'group' => false,
                ])
                ->filter(fn (array $item): bool => ! empty($item['value']))
                ->values()
                ->all();

            foreach ($typeConfig as $type => $config) {
                $sections = [];

                foreach (collect($resourceGroups)->filter(fn (array $g): bool => ($g['type'] ?? null) == $type)->values() as $group) {
                    $members = array_map(fn (string $r): array => [
                        'value' => $r, 'label' => $r, 'group' => true,
                    ], json_decode($group['resources'] ?? '[]', true) ?? []);

                    if (! empty($members)) {
                        $sections[] = ['name' => $group['name'], 'methods' => $members];
                    }
                }

                $sections[] = ['name' => 'Elements', 'methods' => $config['elements']];

                $flat = [];
                foreach ($sections as $section) {
                    $flat[] = ['label' => $section['name'], 'value' => array_column($section['methods'], 'value'), 'isConstructor' => true];
                    foreach ($section['methods'] as $method) {
                        $flat[] = ['label' => $method['label'], 'value' => $method['value'], 'group' => $method['group']];
                    }
                }

                $this->{$config['key']} = array_values(array_filter(
                    $flat,
                    fn (array $el): bool => $el['label'] !== 'Elements' && (! ($el['group'] ?? false) || ($el['isConstructor'] ?? false)),
                ));
            }
        } catch (\Throwable $e) {
            report($e);
            $this->extensionsReduced = [];
            $this->queuesReduced = [];
            $this->didsReduced = [];
        }
    }

    /** @param  array{label: string, value: string|string[]}  $element */
    private function toggleFilterSelection(array $element, array &$selected, string $filterKey): void
    {
        $values = is_array($element['value']) ? $element['value'] : [$element['value']];

        foreach ($values as $raw) {
            $normalized = $this->normalizeValue((string) $raw);
            $idx = array_search($normalized, $selected, true);
            if ($idx !== false) {
                array_splice($selected, (int) $idx, 1);
            } else {
                $selected[] = $normalized;
            }
        }

        $this->{$filterKey} = implode(',', $selected);
    }

    private function normalizeValue(string $raw): string
    {
        $part = explode(' ', $raw)[0];
        $part = (string) preg_replace('/^\*/', '0', $part);

        return (string) preg_replace('/[^0-9]/', '', $part);
    }

    private function applyPeriod(string $period): void
    {
        $today = Carbon::today();

        match ($period) {
            'today' => $this->setRange($today->copy(), $today->copy()),
            'yesterday' => $this->setRange($today->copy()->subDay(), $today->copy()->subDay()),
            'currentWeek' => $this->setRange($today->copy()->startOfWeek(), $today->copy()),
            'lastWeek' => $this->setRange($today->copy()->subWeek()->startOfWeek(), $today->copy()->subWeek()->endOfWeek()),
            'currentMonth' => $this->setRange($today->copy()->startOfMonth(), $today->copy()),
            'lastMonth' => $this->setRange($today->copy()->subMonth()->startOfMonth(), $today->copy()->subMonth()->endOfMonth()),
            default => null,
        };
    }

    private function setRange(Carbon $start, Carbon $end): void
    {
        $this->startDate = $start->format('Y-m-d');
        $this->endDate = $end->format('Y-m-d');
    }
}
