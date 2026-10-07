<?php

use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use CXEngine\ExpertStatistics\Tests\TestCase;
use CXEngine\ExpertStats\ExpertStatisticsConnector;

uses(TestCase::class);

it('builds the service on whatever connector the host app binds', function () {
    $relayConnector = new class('https://relay.test/api/clients', '', '') extends ExpertStatisticsConnector {};

    $this->app->scoped(ExpertStatisticsConnector::class, fn () => $relayConnector);

    $service = app(ExpertStatisticsService::class);

    expect((fn () => $this->connector)->call($service))->toBe($relayConnector);
});

it('rebuilds the connector and service for each request or job, never sharing one across them', function () {
    $connector = app(ExpertStatisticsConnector::class);
    $service = app(ExpertStatisticsService::class);

    expect(app(ExpertStatisticsConnector::class))->toBe($connector)
        ->and(app(ExpertStatisticsService::class))->toBe($service);

    // What Octane and the queue worker do between requests / jobs.
    $this->app->forgetScopedInstances();

    expect(app(ExpertStatisticsConnector::class))->not->toBe($connector)
        ->and(app(ExpertStatisticsService::class))->not->toBe($service);
});
