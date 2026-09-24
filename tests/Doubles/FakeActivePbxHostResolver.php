<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Tests\Doubles;

use CXEngine\ExpertStatistics\Contracts\ResolvesActivePbxHost;

/**
 * Minimal ResolvesActivePbxHost double, bound by default in tests/TestCase.php
 * so container resolution of ExpertStatisticsService (and anything that
 * depends on it) works out of the box in tests — no real host app is
 * present to provide the real implementation. Any test that cares about a
 * specific active host can rebind this via $this->app->bind(...).
 */
class FakeActivePbxHostResolver implements ResolvesActivePbxHost
{
    public function __construct(private ?string $hostName = 'test-host.on3cx.fr') {}

    public function getActiveHostName(): ?string
    {
        return $this->hostName;
    }

    public function getAvailableHosts(): array
    {
        return $this->hostName === null ? [] : [
            ['name' => $this->hostName, 'label' => $this->hostName, 'active' => true],
        ];
    }

    public function setActiveHost(string $hostName): void
    {
        $this->hostName = $hostName;
    }
}
