<?php

namespace CXEngine\ExpertStatistics\Concerns;

use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;

trait HasPbxElementSelector
{
    use PersistsExpertStatisticsFilters;

    public bool $showDatePicker = false;

    /** @var array<int, array{label: string, value: string|string[], isConstructor?: bool, group?: bool}> */
    public array $pbxElements = [];

    /** @var string[] */
    public array $selectedElements = [];

    /** @var string[] Names of currently selected resource groups */
    public array $groupSelectedName = [];

    public bool $groupSelected = false;

    /** @var array<string, string[]> Maps element value → list of group names that claim it */
    public array $elementGroupMap = [];

    public function loadPbxElements(): void
    {
        $service = app(ExpertStatisticsService::class);
        $urlType = $this->urlType ?? 'extension';

        try {
            $resourceGroups = $service->getResourceGroups();

            $groupTypeForUrlType = [
                'extension' => 0,
                'queue' => 4,
                'did' => 1,
                'caller' => 99,
            ];
            $groupType = $groupTypeForUrlType[$urlType] ?? 0;

            $filteredGroups = collect($resourceGroups)
                ->filter(fn (array $g): bool => ($g['type'] ?? null) == $groupType)
                ->values();

            $sections = [];

            if ($urlType === 'caller') {
                foreach ($filteredGroups as $group) {
                    $resources = json_decode($group['resources'] ?? '[]', true) ?? [];
                    $members = array_map(fn (string $r): array => [
                        'value' => $r,
                        'label' => $r,
                        'group' => true,
                    ], $resources);

                    if (! empty($members)) {
                        $sections[] = ['name' => $group['name'], 'methods' => $members];
                    }
                }
            } else {
                $map = $service->getMap();

                foreach ($filteredGroups as $group) {
                    $resources = json_decode($group['resources'] ?? '[]', true) ?? [];
                    $members = [];

                    foreach ($resources as $r) {
                        $members[] = [
                            'value' => (string) $r,
                            'label' => (string) $r,
                            'group' => true,
                        ];
                    }

                    if (! empty($members)) {
                        $sections[] = ['name' => $group['name'], 'methods' => $members];
                    }
                }

                $elements = match ($urlType) {
                    'queue' => collect($map['call_queues'] ?? [])
                        ->map(fn (mixed $q, string $dn): array => [
                            'value' => $dn,
                            'label' => $dn.' — '.(is_array($q) ? ($q['name'] ?? $dn) : $dn),
                            'group' => false,
                        ])
                        ->values()
                        ->all(),
                    'did' => collect($map['dids'] ?? [])
                        ->map(fn (mixed $v): array => [
                            'value' => (string) (is_array($v) ? ($v['number'] ?? $v) : $v),
                            'label' => (string) (is_array($v) ? ($v['number'] ?? $v) : $v),
                            'group' => false,
                        ])
                        ->unique('value')
                        ->values()
                        ->all(),
                    default => collect($map['extensions'] ?? [])
                        ->map(fn (mixed $name, string $dn): array => [
                            'value' => $dn,
                            'label' => $dn.($name !== null ? ' — '.$name : ''),
                            'group' => false,
                        ])
                        ->values()
                        ->all(),
                };

                $sections[] = ['name' => 'Elements', 'methods' => $elements];
            }

            // Reduce to flat interleaved array of group headers + individual elements
            $flat = [];
            foreach ($sections as $section) {
                $flat[] = [
                    'label' => $section['name'],
                    'value' => array_column($section['methods'], 'value'),
                    'isConstructor' => true,
                ];
                foreach ($section['methods'] as $method) {
                    $flat[] = [
                        'label' => $method['label'],
                        'value' => $method['value'],
                        'group' => $method['group'],
                    ];
                }
            }

            // Remove "Elements" section header and group-member rows (group=true, isConstructor absent)
            $this->pbxElements = array_values(array_filter(
                $flat,
                fn (array $el): bool => $el['label'] !== 'Elements'
                    && (! ($el['group'] ?? false) || ($el['isConstructor'] ?? false)),
            ));

        } catch (\Throwable $e) {
            report($e);
            $this->pbxElements = [];
        }

        // Sync selectedElements from URL $dn parameter
        if (! empty($this->dn)) {
            $this->selectedElements = array_values(array_filter(
                explode(',', $this->dn),
                fn (string $d): bool => $d !== '',
            ));
        }
    }

    /**
     * Called from the element-selector dropdown for both individual elements (value = string)
     * and group headers (value = string[]).
     *
     * @param  array{label: string, value: string|string[], isConstructor?: bool, group?: bool}  $element
     */
    public function addElement(array $element): void
    {
        $value = $element['value'];

        if (is_array($value)) {
            $groupLabel = $element['label'];
            $isCurrentlySelected = in_array($groupLabel, $this->groupSelectedName, true);

            if ($isCurrentlySelected) {
                $this->groupSelectedName = array_values(
                    array_filter($this->groupSelectedName, fn (string $g): bool => $g !== $groupLabel),
                );
            } else {
                $this->groupSelectedName[] = $groupLabel;
            }

            foreach ($value as $rawVal) {
                $val = $this->normalizeValue((string) $rawVal);

                if (! isset($this->elementGroupMap[$val])) {
                    $this->elementGroupMap[$val] = [];
                }

                if (! $isCurrentlySelected) {
                    if (! in_array($groupLabel, $this->elementGroupMap[$val], true)) {
                        $this->elementGroupMap[$val][] = $groupLabel;
                    }
                    if (! in_array($val, $this->selectedElements, true)) {
                        $this->selectedElements[] = $val;
                    }
                } else {
                    $this->elementGroupMap[$val] = array_values(
                        array_filter($this->elementGroupMap[$val], fn (string $g): bool => $g !== $groupLabel),
                    );

                    $stillClaimed = false;
                    foreach ($this->groupSelectedName as $otherGroup) {
                        if (in_array($otherGroup, $this->elementGroupMap[$val], true)) {
                            $stillClaimed = true;
                            break;
                        }
                    }

                    if (! $stillClaimed) {
                        $this->selectedElements = array_values(
                            array_filter($this->selectedElements, fn (string $e): bool => $e !== $val),
                        );
                    }
                }
            }

            $this->groupSelected = ! empty($this->groupSelectedName);
            $this->dispatch('groupSelectedChanged', $this->groupSelected);
            $this->dispatch('groupSelectedNameChanged', $this->groupSelectedName);
            $this->applyPbxElementSelection();

            return;
        }

        // Individual element
        $val = $this->normalizeValue((string) $value);
        $idx = array_search($val, $this->selectedElements, true);

        if ($idx !== false) {
            array_splice($this->selectedElements, (int) $idx, 1);
        } else {
            $this->selectedElements[] = $val;
        }

        $this->applyPbxElementSelection();
    }

    public function removePbxElement(string $dn): void
    {
        $this->selectedElements = array_values(
            array_filter($this->selectedElements, fn (string $e): bool => $e !== $dn),
        );
        $this->applyPbxElementSelection();
    }

    public function removePbxGroup(string $groupName): void
    {
        $group = collect($this->pbxElements)
            ->first(fn (array $e): bool => ($e['isConstructor'] ?? false) && $e['label'] === $groupName);

        if ($group !== null) {
            $this->addElement($group);
        }
    }

    public function selectAllPbxElements(): void
    {
        $individuals = collect($this->pbxElements)
            ->filter(fn (array $e): bool => ! ($e['isConstructor'] ?? false))
            ->pluck('value')
            ->map(fn (string $v): string => $this->normalizeValue($v))
            ->unique()
            ->values();

        if ($individuals->isNotEmpty()) {
            // Normal case (extension / queue / did): select every individual element
            $this->selectedElements = $individuals->all();
        } else {
            // Caller type: the list contains only group headers — select all group members
            foreach ($this->pbxElements as $elem) {
                if (! ($elem['isConstructor'] ?? false)) {
                    continue;
                }
                $groupLabel = $elem['label'];
                if (! in_array($groupLabel, $this->groupSelectedName, true)) {
                    $this->groupSelectedName[] = $groupLabel;
                }
                foreach ((array) $elem['value'] as $rawVal) {
                    $val = $this->normalizeValue((string) $rawVal);
                    if (! isset($this->elementGroupMap[$val])) {
                        $this->elementGroupMap[$val] = [];
                    }
                    if (! in_array($groupLabel, $this->elementGroupMap[$val], true)) {
                        $this->elementGroupMap[$val][] = $groupLabel;
                    }
                    if (! in_array($val, $this->selectedElements, true)) {
                        $this->selectedElements[] = $val;
                    }
                }
            }
            $this->groupSelected = ! empty($this->groupSelectedName);
        }

        $this->applyPbxElementSelection();
    }

    public function clearPbxElements(): void
    {
        $this->selectedElements = [];
        $this->groupSelectedName = [];
        $this->groupSelected = false;
        $this->elementGroupMap = [];
        $this->dn = '';
        $this->saveExpertStatsElements();
        if (method_exists($this, 'loadData')) {
            $this->loadData();
        }
    }

    public function applyFilters(): void
    {
        if (method_exists($this, 'loadData')) {
            $this->loadData();
        }
    }

    public function applyPbxElementSelection(): void
    {
        $this->dn = implode(',', $this->selectedElements);
        $this->saveExpertStatsElements();
        if (method_exists($this, 'loadData')) {
            $this->loadData();
        }
    }

    public function updateTimeRange(): void
    {
        $this->saveExpertStatsTime();
        if (! empty($this->dn) && method_exists($this, 'loadData')) {
            $this->loadData();
        }
    }

    public function setCustomRange(string $start, string $end): void
    {
        $this->startDate = $start;
        $this->endDate = $end;
        $this->selectedPeriod = 'custom';
        $this->showDatePicker = false;
        $this->saveExpertStatsPeriod();
        if (method_exists($this, 'loadData')) {
            $this->loadData();
        }
    }

    private function normalizeValue(string $raw): string
    {
        $part = explode(' ', $raw)[0];
        $part = (string) preg_replace('/^\*/', '0', $part);

        return (string) preg_replace('/[^0-9]/', '', $part);
    }
}
