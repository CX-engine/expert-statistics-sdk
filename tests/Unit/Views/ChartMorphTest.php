<?php

use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

uses(TestCase::class);

/*
 * ApexCharts draws into its x-ref element client-side, so the server always
 * renders that element empty. When a re-render keeps the same chartKey (the
 * data did not change, e.g. toggling "Exclude closed hours" on a period with
 * no closed hours), Livewire morphs the chart in place: without wire:ignore it
 * empties the drawn chart, and Alpine never re-runs init() to draw it again.
 */

$row = ['period' => '09:00', 'answered' => 5, 'unanswered' => 3, 'total' => 8, 'rate' => 62.5, 'avg_wait' => 12];

dataset('charts', [
    'kpi chart' => ['<x-expert-statistics::charts.kpi-chart title="T" :rows="$rows" chartKey="k" />', ['chart', 'waitChart']],
    'stacked bar chart' => ['<x-expert-statistics::charts.stacked-bar-chart title="T" :categories="[\'09:00\']" :series="[[\'name\' => \'A\', \'data\' => [1]]]" chartKey="k" />', ['chart']],
    'heatmap chart' => ['<x-expert-statistics::charts.heatmap-chart title="T" :series="[[\'name\' => \'Mon\', \'data\' => [[\'x\' => \'09:00\', \'y\' => 1]]]]" chartKey="k" />', ['heatmap']],
    'donut chart' => ['<x-expert-statistics::charts.donut-chart title="T" :items="[[\'label\' => \'A\', \'minutes\' => 3]]" chartKey="k" />', ['donut']],
    'origin donut' => ['<x-expert-statistics::charts.origin-donut title="T" :details="[[\'inbound_calls\' => 3, \'origin_dn_type\' => 1]]" :isTop10="false" :typeLabels="[1 => \'Externe\']" chartKey="k" />', ['donut']],
]);

it('keeps the drawn chart out of Livewire morphs', function (string $blade, array $refs) use ($row) {
    $html = Blade::render($blade, ['rows' => [$row]]);

    expect($html)->toContain('wire:key="k"');

    foreach ($refs as $ref) {
        expect($html)->toContain("x-ref=\"{$ref}\" wire:ignore");
    }
})->with('charts');
