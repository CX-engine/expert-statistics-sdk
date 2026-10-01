<?php

use CXEngine\ExpertStatistics\Support\CallAnalysisLink;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

uses(TestCase::class);

function registerCallAnalysisRoute(): void
{
    Route::get('/expert-statistics/call-analysis', fn () => '')->name(CallAnalysisLink::ROUTE);
    Route::getRoutes()->refreshNameLookups();
}

it('returns null when the host app has not registered the call analysis route', function () {
    expect(CallAnalysisLink::url(['callStatus' => 'all']))->toBeNull();
});

it('builds a pre-filtered call analysis url, dropping empty params', function () {
    registerCallAnalysisRoute();

    $url = CallAnalysisLink::url([
        ...CallAnalysisLink::period('2026-09-01', '2026-09-30', '07:00', '19:00'),
        'callWay' => 'inbound',
        'destinationDn' => '801,802', 'destinationDnType' => '4',
        'originDn' => '', 'originDnType' => null,
        'callStatus' => 'unanswered',
    ]);

    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect(parse_url($url, PHP_URL_PATH))->toBe('/expert-statistics/call-analysis')
        ->and($query)->toBe([
            'selectedPeriod' => 'custom',
            'startDate' => '2026-09-01', 'endDate' => '2026-09-30',
            'startTime' => '07:00', 'endTime' => '19:00',
            'callWay' => 'inbound',
            'destinationDn' => '801,802', 'destinationDnType' => '4',
            'callStatus' => 'unanswered',
        ]);
});

it('converts a report day key to a date', function () {
    expect(CallAnalysisLink::dayToDate('20260915'))->toBe('2026-09-15')
        ->and(CallAnalysisLink::dayToDate(''))->toBeNull()
        ->and(CallAnalysisLink::dayToDate(null))->toBeNull();
});

it('renders the stat as a new-tab link when there is a url', function () {
    $html = Blade::render('<x-expert-statistics::call-analysis-link :href="$href">42</x-expert-statistics::call-analysis-link>', ['href' => 'https://example.test/cfa?x=1']);

    expect($html)->toContain('<a href="https://example.test/cfa?x=1" target="_blank"')
        ->toContain('hover:underline')
        ->toContain('>42</a>');
});

it('renders the bare stat when there is no url', function () {
    $html = Blade::render('<x-expert-statistics::call-analysis-link :href="null">42</x-expert-statistics::call-analysis-link>');

    expect(trim($html))->toBe('42');
});

it('keeps a tile box when there is no url', function () {
    $html = Blade::render('<x-expert-statistics::call-analysis-link tile :href="null" class="rounded-xl">42</x-expert-statistics::call-analysis-link>');

    expect(trim($html))->toBe('<div class="rounded-xl">42</div>');
});
