<?php

namespace CXEngine\ExpertStatistics\Services;

use CXEngine\ExpertStatistics\Contracts\ResolvesActivePbxHost;
use CXEngine\ExpertStatistics\Exceptions\NoActivePbxHostException;
use CXEngine\ExpertStats\ExpertStatisticsConnector;
use Illuminate\Support\Facades\Cache;

/**
 * Caching facade over the cx-engine/expert-stats-api-sdk-php StatsResource,
 * mirroring bluerocktelclients' App\Services\PbxApiService: one method per
 * report/KPI endpoint, response cached with the same TTLs. Unlike
 * PbxApiService, the active host is resolved via ResolvesActivePbxHost
 * instead of a session key, and there is no per-user token — the SDK
 * connector authenticates once as a shared service account.
 */
class ExpertStatisticsService
{
    public function __construct(
        private readonly ExpertStatisticsConnector $connector,
        private readonly ResolvesActivePbxHost $hostResolver,
        private readonly int $defaultTtl = 300,
    ) {}

    public function hostName(): string
    {
        return $this->hostResolver->getActiveHostName() ?? throw NoActivePbxHostException::make();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function getUniqueCallsPeriod(array $query = []): array
    {
        return $this->remember('unique_calls', $query, fn (string $host): array => $this->connector
            ->stats()
            ->uniqueCallsPeriod($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function getInboundCalls(string $type, array $query = [], bool $preAnswer = false): array
    {
        return $this->remember("inbound.{$type}.".($preAnswer ? '1' : '0'), $query, fn (string $host): array => $this->connector
            ->stats()
            ->inboundCalls($host, $type, $query, $preAnswer)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function getOutboundCalls(array $query = []): array
    {
        return $this->remember('outbound', $query, fn (string $host): array => $this->connector
            ->stats()
            ->outboundCalls($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function getUsersCalls(array $query = []): array
    {
        return $this->remember('users_calls', $query, fn (string $host): array => $this->connector
            ->stats()
            ->usersCalls($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function getTrendMonthly(array $query = []): array
    {
        return $this->remember('trend_monthly', $query, fn (string $host): array => $this->connector
            ->stats()
            ->trendMonthly($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function getKpi(string $type, string $granularity, array $query = []): array
    {
        return $this->remember("kpi.{$type}.{$granularity}", $query, fn (string $host): array => $this->connector
            ->stats()
            ->kpi($host, $type, $granularity, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function getQueuesReport(array $query = []): array
    {
        return $this->remember('queues_report', $query, fn (string $host): array => $this->connector
            ->stats()
            ->queuesReport($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function getUsersReport(array $query = []): array
    {
        return $this->remember('users_report', $query, fn (string $host): array => $this->connector
            ->stats()
            ->usersReport($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getStandardDashboard(array $query = []): array
    {
        return $this->remember('standard_dashboard', $query, fn (string $host): array => $this->connector
            ->stats()
            ->standardDashboard($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getCallersReport(array $query = []): array
    {
        return $this->remember('callers_report', $query, fn (string $host): array => $this->connector
            ->stats()
            ->callersReport($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getDidReport(array $query = []): array
    {
        return $this->remember('did_report', $query, fn (string $host): array => $this->connector
            ->stats()
            ->didReport($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchCallerNumbers(string $search): array
    {
        $host = $this->hostName();

        return $this->connector->stats()->searchCallerNumbers($host, $search)->throw()->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function getMap(): array
    {
        return $this->remember('map', [], fn (string $host): array => $this->connector
            ->stats()
            ->map($host)
            ->throw()
            ->json());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getResourceGroups(): array
    {
        return $this->remember('resource_groups', [], fn (string $host): array => $this->connector
            ->stats()
            ->resourceGroups($host)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getOrigin(string $type, array $query = [], bool $top = false): array
    {
        return $this->remember('origin.'.($top ? 'top.' : '').$type, $query, fn (string $host): array => $this->connector
            ->stats()
            ->origin($host, $type, $query, $top)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function remember(string $keySuffix, array $query, \Closure $resolve, ?int $ttl = null): array
    {
        $host = $this->hostName();
        $cacheKey = "expert-statistics.{$keySuffix}.{$host}.".md5(serialize($query));

        return Cache::remember($cacheKey, $ttl ?? $this->defaultTtl, fn (): array => $resolve($host));
    }
}
