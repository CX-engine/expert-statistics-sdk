<?php

namespace CXEngine\ExpertStatistics\Services;

use CXEngine\ExpertStatistics\Contracts\ResolvesActivePbxHost;
use CXEngine\ExpertStatistics\Exceptions\NoActivePbxHostException;
use CXEngine\ExpertStats\ExpertStatisticsConnector;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Response;

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

    // --- Report Table Configs ---
    // Not host-scoped by the backend (scoped by the XP-Stats customer
    // identifier instead) - the active host is still resolved here for
    // consistency with every other method in this class. getPublicWallboard()
    // below is the one deliberate exception to that rule.

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getReportTableConfigs(): array
    {
        return $this->remember('report_table_configs', [], fn (): array => $this->connector
            ->reportTableConfig()
            ->index()
            ->throw()
            ->json());
    }

    /**
     * @return array<string, mixed>
     */
    public function getReportTableConfig(string $customer): array
    {
        return $this->remember('report_table_config.'.$customer, [], fn (): array => $this->connector
            ->reportTableConfig()
            ->show($customer)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createReportTableConfig(array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->reportTableConfig()->store($data)->throw()->json();

        $this->forget($host, 'report_table_configs');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateReportTableConfig(string $customer, array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->reportTableConfig()->update($customer, $data)->throw()->json();

        $this->forget($host, 'report_table_configs');
        $this->forget($host, 'report_table_config.'.$customer);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteReportTableConfig(int $id): array
    {
        $host = $this->hostName();

        $result = $this->connector->reportTableConfig()->delete($id)->throw()->json();

        $this->forget($host, 'report_table_configs');

        return $result;
    }

    // --- Agent Configuration ---

    /**
     * @return array<string, mixed>
     */
    public function getAgentConfiguration(): array
    {
        return $this->remember('agent_configuration', [], fn (string $host): array => $this->connector
            ->agentConfiguration()
            ->show($host)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createAgentConfiguration(array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->agentConfiguration()->store($host, $data)->throw()->json();

        $this->forget($host, 'agent_configuration');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateAgentConfiguration(array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->agentConfiguration()->update($host, $data)->throw()->json();

        $this->forget($host, 'agent_configuration');

        return $result;
    }

    // --- Preanswer Times ---

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function getPreanswerTimes(array $query = []): array
    {
        return $this->remember('preanswer_times', $query, fn (string $host): array => $this->connector
            ->preanswerTime()
            ->index($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createPreanswerTime(array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->preanswerTime()->store($host, $data)->throw()->json();

        $this->forget($host, 'preanswer_times');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function deletePreanswerTime(array $query = []): array
    {
        $host = $this->hostName();

        $result = $this->connector->preanswerTime()->delete($host, $query)->throw()->json();

        $this->forget($host, 'preanswer_times');

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public function bulkCreatePreanswerTimes(array $items): array
    {
        $host = $this->hostName();

        $result = $this->connector->preanswerTime()->bulkStore($host, $items)->throw()->json();

        $this->forget($host, 'preanswer_times');

        return $result;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<string, mixed>
     */
    public function bulkDeletePreanswerTimes(array $ids): array
    {
        $host = $this->hostName();

        $result = $this->connector->preanswerTime()->bulkDelete($host, $ids)->throw()->json();

        $this->forget($host, 'preanswer_times');

        return $result;
    }

    // --- Resource Groups ---
    // Write CRUD for individual resource groups. The cached list read is
    // StatsResource-backed - see getResourceGroups() above.

    /**
     * @return array<string, mixed>
     */
    public function getResourceGroup(int $id): array
    {
        return $this->remember('resource_group.'.$id, [], fn (string $host): array => $this->connector
            ->resourceGroup()
            ->show($host, $id)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createResourceGroup(array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->resourceGroup()->store($host, $data)->throw()->json();

        $this->forget($host, 'resource_groups');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateResourceGroup(int $id, array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->resourceGroup()->update($host, $id, $data)->throw()->json();

        $this->forget($host, 'resource_groups');
        $this->forget($host, 'resource_group.'.$id);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteResourceGroup(int $id): array
    {
        $host = $this->hostName();

        $result = $this->connector->resourceGroup()->delete($host, $id)->throw()->json();

        $this->forget($host, 'resource_groups');
        $this->forget($host, 'resource_group.'.$id);

        return $result;
    }

    // --- Host Audits ---

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getHostAudits(): array
    {
        return $this->remember('host_audits', [], fn (string $host): array => $this->connector
            ->hostAudit()
            ->index($host)
            ->throw()
            ->json());
    }

    /**
     * @return array<string, mixed>
     */
    public function getHostAudit(int $id): array
    {
        return $this->remember('host_audit.'.$id, [], fn (string $host): array => $this->connector
            ->hostAudit()
            ->show($host, $id)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createHostAudit(array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->hostAudit()->store($host, $data)->throw()->json();

        $this->forget($host, 'host_audits');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateHostAudit(int $id, array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->hostAudit()->update($host, $id, $data)->throw()->json();

        $this->forget($host, 'host_audits');
        $this->forget($host, 'host_audit.'.$id);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteHostAudit(int $id): array
    {
        $host = $this->hostName();

        $result = $this->connector->hostAudit()->delete($host, $id)->throw()->json();

        $this->forget($host, 'host_audits');
        $this->forget($host, 'host_audit.'.$id);

        return $result;
    }

    // --- Host Notes ---

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getHostNotes(): array
    {
        return $this->remember('host_notes', [], fn (string $host): array => $this->connector
            ->hostNote()
            ->index($host)
            ->throw()
            ->json());
    }

    /**
     * @return array<string, mixed>
     */
    public function getHostNote(int $id): array
    {
        return $this->remember('host_note.'.$id, [], fn (string $host): array => $this->connector
            ->hostNote()
            ->show($host, $id)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createHostNote(array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->hostNote()->store($host, $data)->throw()->json();

        $this->forget($host, 'host_notes');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateHostNote(int $id, array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->hostNote()->update($host, $id, $data)->throw()->json();

        $this->forget($host, 'host_notes');
        $this->forget($host, 'host_note.'.$id);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteHostNote(int $id): array
    {
        $host = $this->hostName();

        $result = $this->connector->hostNote()->delete($host, $id)->throw()->json();

        $this->forget($host, 'host_notes');
        $this->forget($host, 'host_note.'.$id);

        return $result;
    }

    // --- CDR ---

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getCdrReport(array $query = []): array
    {
        return $this->remember('cdr_report', $query, fn (string $host): array => $this->connector
            ->cdr()
            ->report($host, $query)
            ->throw()
            ->json());
    }

    /**
     * Streams a binary xlsx export. Callers must NOT call ->json() on the
     * returned response - use ->body()/->stream() instead.
     *
     * @param  array<string, mixed>  $query
     */
    public function streamCdrExport(array $query = []): Response
    {
        $host = $this->hostName();

        return $this->connector->cdr()->export($host, $query)->throw();
    }

    // --- Reports ---
    // Scheduled report definitions. Does not wrap the public "generate"/
    // "unsubscribe" routes hit directly by email recipients.

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function getReports(array $query = []): array
    {
        return $this->remember('reports', $query, fn (string $host): array => $this->connector
            ->report()
            ->index($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createReport(array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->report()->store($host, $data)->throw()->json();

        $this->forget($host, 'reports');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateReport(string $id, array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->report()->update($host, $id, $data)->throw()->json();

        $this->forget($host, 'reports');

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteReport(string $id): array
    {
        $host = $this->hostName();

        $result = $this->connector->report()->delete($host, $id)->throw()->json();

        $this->forget($host, 'reports');

        return $result;
    }

    // --- Alerts ---

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAlerts(): array
    {
        return $this->remember('alerts', [], fn (string $host): array => $this->connector
            ->alert()
            ->index($host)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createAlert(array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->alert()->store($host, $data)->throw()->json();

        $this->forget($host, 'alerts');

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteAlert(int $id): array
    {
        $host = $this->hostName();

        $result = $this->connector->alert()->delete($host, $id)->throw()->json();

        $this->forget($host, 'alerts');

        return $result;
    }

    // --- Wallboards ---
    // Saved wallboard layout configs. For the live wallboard data itself,
    // see Wallboard Data below.

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getWallboards(): array
    {
        return $this->remember('wallboards', [], fn (string $host): array => $this->connector
            ->wallboard()
            ->index($host)
            ->throw()
            ->json());
    }

    /**
     * @return array<string, mixed>
     */
    public function getWallboard(string $uuid): array
    {
        return $this->remember('wallboard.'.$uuid, [], fn (string $host): array => $this->connector
            ->wallboard()
            ->show($host, $uuid)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createWallboard(array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->wallboard()->store($host, $data)->throw()->json();

        $this->forget($host, 'wallboards');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateWallboard(string $uuid, array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->wallboard()->update($host, $uuid, $data)->throw()->json();

        $this->forget($host, 'wallboards');
        $this->forget($host, 'wallboard.'.$uuid);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteWallboard(string $uuid): array
    {
        $host = $this->hostName();

        $result = $this->connector->wallboard()->delete($host, $uuid)->throw()->json();

        $this->forget($host, 'wallboards');
        $this->forget($host, 'wallboard.'.$uuid);

        return $result;
    }

    /**
     * AI-generates a wallboard layout. Not cached - this is a slow AI
     * generation call, and the result is a draft the caller still has to
     * persist via createWallboard()/updateWallboard().
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function buildWallboardWithAi(array $data): array
    {
        $host = $this->hostName();

        return $this->connector->wallboard()->build($host, $data)->throw()->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function activateWallboard(string $uuid): array
    {
        $host = $this->hostName();

        $result = $this->connector->wallboard()->setActive($host, $uuid)->throw()->json();

        $this->forget($host, 'wallboards');
        $this->forget($host, 'wallboard.'.$uuid);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function revertWallboard(string $uuid): array
    {
        $host = $this->hostName();

        $result = $this->connector->wallboard()->revert($host, $uuid)->throw()->json();

        $this->forget($host, 'wallboards');
        $this->forget($host, 'wallboard.'.$uuid);

        return $result;
    }

    // --- Wallboard Data ---
    // Live wallboard tile data. For the saved layout configs, see
    // Wallboards above.

    /**
     * @return array<string, mixed>
     */
    public function getWallboardHasData(bool $aggregated = false): array
    {
        return $this->remember('wallboard_data.has_data.'.($aggregated ? '1' : '0'), [], fn (string $host): array => $this->connector
            ->wallboardData()
            ->hasData($host, $aggregated)
            ->throw()
            ->json());
    }

    /**
     * @return array<string, mixed>
     */
    public function getWallboardLive(bool $aggregated = false): array
    {
        return $this->remember('wallboard_data.live.'.($aggregated ? '1' : '0'), [], fn (string $host): array => $this->connector
            ->wallboardData()
            ->live($host, $aggregated)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getWallboardSummary(array $query = [], bool $aggregated = false): array
    {
        return $this->remember('wallboard_data.summary.'.($aggregated ? '1' : '0'), $query, fn (string $host): array => $this->connector
            ->wallboardData()
            ->summary($host, $query, $aggregated)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getWallboardOverview(array $query = [], bool $aggregated = false): array
    {
        return $this->remember('wallboard_data.overview.'.($aggregated ? '1' : '0'), $query, fn (string $host): array => $this->connector
            ->wallboardData()
            ->overview($host, $query, $aggregated)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getWallboardTimeseries(array $query = [], bool $aggregated = false): array
    {
        return $this->remember('wallboard_data.timeseries.'.($aggregated ? '1' : '0'), $query, fn (string $host): array => $this->connector
            ->wallboardData()
            ->timeseries($host, $query, $aggregated)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getWallboardCalls(array $query = [], bool $aggregated = false): array
    {
        return $this->remember('wallboard_data.calls.'.($aggregated ? '1' : '0'), $query, fn (string $host): array => $this->connector
            ->wallboardData()
            ->calls($host, $query, $aggregated)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getWallboardMeta(array $query = [], bool $aggregated = false): array
    {
        return $this->remember('wallboard_data.meta.'.($aggregated ? '1' : '0'), $query, fn (string $host): array => $this->connector
            ->wallboardData()
            ->meta($host, $query, $aggregated)
            ->throw()
            ->json());
    }

    /**
     * Resolves live values for an arbitrary set of wallboard tiles. Not
     * cached - a wallboard editor needs fresh values while composing tiles.
     *
     * @param  array<int, array<string, mixed>>  $tiles
     * @return array<string, mixed>
     */
    public function resolveWallboardTiles(array $tiles, bool $aggregated = false): array
    {
        $host = $this->hostName();

        return $this->connector->wallboardData()->resolve($host, $tiles, $aggregated)->throw()->json();
    }

    /**
     * Fetches a publicly shared wallboard's live data by its share key.
     *
     * This is the one method in this class that does NOT resolve an active
     * host: callers are unauthenticated, anonymous visitors following a
     * public share link, so there is no "active PBX host" in scope for
     * them - the share key alone identifies the host and wallboard
     * server-side.
     *
     * @return array<string, mixed>
     */
    public function getPublicWallboard(string $key): array
    {
        return $this->connector->wallboardData()->publicShare($key)->throw()->json();
    }

    // --- Agent Stats ---
    // Read-only realtime/historical agent statistics, all cached at the
    // default TTL.

    /**
     * @return array<string, mixed>
     */
    public function getAgentStatsHasData(): array
    {
        return $this->remember('agent_stats.has_data', [], fn (string $host): array => $this->connector
            ->agentStats()
            ->hasData($host)
            ->throw()
            ->json());
    }

    /**
     * @return array<string, mixed>
     */
    public function getAgentStatsHasDataLive(): array
    {
        return $this->remember('agent_stats.has_data_live', [], fn (string $host): array => $this->connector
            ->agentStats()
            ->hasDataLive($host)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getAgentStatsOverview(array $query = []): array
    {
        return $this->remember('agent_stats.overview', $query, fn (string $host): array => $this->connector
            ->agentStats()
            ->overview($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getAgentStatsStatusBreakdown(array $query = []): array
    {
        return $this->remember('agent_stats.status_breakdown', $query, fn (string $host): array => $this->connector
            ->agentStats()
            ->statusBreakdown($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getAgentStatsQueueCoverageUsers(array $query = []): array
    {
        return $this->remember('agent_stats.queue_coverage_users', $query, fn (string $host): array => $this->connector
            ->agentStats()
            ->queueCoverageUsers($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getAgentStatsQueueCoverageQueues(array $query = []): array
    {
        return $this->remember('agent_stats.queue_coverage_queues', $query, fn (string $host): array => $this->connector
            ->agentStats()
            ->queueCoverageQueues($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getAgentStatsQueueCoverage(array $query = []): array
    {
        return $this->remember('agent_stats.queue_coverage', $query, fn (string $host): array => $this->connector
            ->agentStats()
            ->queueCoverage($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @return array<string, mixed>
     */
    public function getAgentStatsRealtimeStatus(): array
    {
        return $this->remember('agent_stats.realtime_status', [], fn (string $host): array => $this->connector
            ->agentStats()
            ->realtimeStatus($host)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getAgentStatsDailyActivity(array $query = []): array
    {
        return $this->remember('agent_stats.daily_activity', $query, fn (string $host): array => $this->connector
            ->agentStats()
            ->dailyActivity($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getAgentStatsQueueConnection(array $query = []): array
    {
        return $this->remember('agent_stats.queue_connection', $query, fn (string $host): array => $this->connector
            ->agentStats()
            ->queueConnection($host, $query)
            ->throw()
            ->json());
    }

    // --- AI: Chat ---
    // Chat history is NOT cached - it must reflect the just-sent message
    // immediately, which a TTL-based cache would delay.

    /**
     * @return array<string, mixed>
     */
    public function sendAiChatMessage(string $message, ?string $conversationUuid = null): array
    {
        $host = $this->hostName();

        return $this->connector->ai()->chat($host, $message, $conversationUuid)->throw()->json();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAiChatHistory(): array
    {
        $host = $this->hostName();

        return $this->connector->ai()->chatHistory($host)->throw()->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAiChatHistoryItem(string $uuid): array
    {
        $host = $this->hostName();

        return $this->connector->ai()->showChatHistory($host, $uuid)->throw()->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteAiChatHistory(): array
    {
        $host = $this->hostName();

        return $this->connector->ai()->deleteChatHistory($host)->throw()->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAiUsage(): array
    {
        return $this->remember('ai.usage', [], fn (string $host): array => $this->connector
            ->ai()
            ->usage($host)
            ->throw()
            ->json());
    }

    // --- AI: Reports ---

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAiReports(): array
    {
        return $this->remember('ai.reports', [], fn (string $host): array => $this->connector
            ->ai()
            ->reports($host)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createAiReport(array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->ai()->storeReport($host, $data)->throw()->json();

        $this->forget($host, 'ai.reports');

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAiReport(string $uuid): array
    {
        return $this->remember('ai.report.'.$uuid, [], fn (string $host): array => $this->connector
            ->ai()
            ->showReport($host, $uuid)
            ->throw()
            ->json());
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteAiReport(string $uuid): array
    {
        $host = $this->hostName();

        $result = $this->connector->ai()->deleteReport($host, $uuid)->throw()->json();

        $this->forget($host, 'ai.reports');
        $this->forget($host, 'ai.report.'.$uuid);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function sendAiReport(string $uuid): array
    {
        $host = $this->hostName();

        return $this->connector->ai()->sendReport($host, $uuid)->throw()->json();
    }

    // --- AI: Analytics ---
    // All cached - these are pre-computed by the backend, not interactive.

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getAiAnalyticsCallLoss(array $query = []): array
    {
        return $this->remember('ai.analytics.call_loss', $query, fn (string $host): array => $this->connector
            ->ai()
            ->analyticsCallLoss($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getAiAnalyticsPeakHours(array $query = []): array
    {
        return $this->remember('ai.analytics.peak_hours', $query, fn (string $host): array => $this->connector
            ->ai()
            ->analyticsPeakHours($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getAiAnalyticsPeriodComparison(array $query = []): array
    {
        return $this->remember('ai.analytics.period_comparison', $query, fn (string $host): array => $this->connector
            ->ai()
            ->analyticsPeriodComparison($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getAiAnalyticsAgentPerformance(array $query = []): array
    {
        return $this->remember('ai.analytics.agent_performance', $query, fn (string $host): array => $this->connector
            ->ai()
            ->analyticsAgentPerformance($host, $query)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getAiAnalyticsAgentStatus(array $query = []): array
    {
        return $this->remember('ai.analytics.agent_status', $query, fn (string $host): array => $this->connector
            ->ai()
            ->analyticsAgentStatus($host, $query)
            ->throw()
            ->json());
    }

    // --- AI: Alerts ---

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAiAlerts(): array
    {
        return $this->remember('ai.alerts', [], fn (string $host): array => $this->connector
            ->ai()
            ->alerts($host)
            ->throw()
            ->json());
    }

    /**
     * @return array<string, mixed>
     */
    public function checkAiAlertsNow(): array
    {
        $host = $this->hostName();

        $result = $this->connector->ai()->checkAlertsNow($host)->throw()->json();

        $this->forget($host, 'ai.alerts');

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function dismissAiAlert(int $alert): array
    {
        $host = $this->hostName();

        $result = $this->connector->ai()->dismissAlert($host, $alert)->throw()->json();

        $this->forget($host, 'ai.alerts');

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAiAlertSettings(): array
    {
        return $this->remember('ai.alert_settings', [], fn (string $host): array => $this->connector
            ->ai()
            ->alertSettings($host)
            ->throw()
            ->json());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateAiAlertSettings(array $data): array
    {
        $host = $this->hostName();

        $result = $this->connector->ai()->updateAlertSettings($host, $data)->throw()->json();

        $this->forget($host, 'ai.alert_settings');

        return $result;
    }

    // --- AI: Dashboard ---

    /**
     * @return array<string, mixed>
     */
    public function getAiDashboard(): array
    {
        return $this->remember('ai.dashboard', [], fn (string $host): array => $this->connector
            ->ai()
            ->dashboard($host)
            ->throw()
            ->json());
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshAiDashboard(): array
    {
        $host = $this->hostName();

        $result = $this->connector->ai()->refreshDashboard($host)->throw()->json();

        $this->forget($host, 'ai.dashboard');

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function readAllAiInsights(): array
    {
        $host = $this->hostName();

        $result = $this->connector->ai()->readAllInsights($host)->throw()->json();

        $this->forget($host, 'ai.dashboard');

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function readAiInsight(string $uuid): array
    {
        $host = $this->hostName();

        $result = $this->connector->ai()->readInsight($host, $uuid)->throw()->json();

        $this->forget($host, 'ai.dashboard');

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function dismissAiInsight(string $uuid): array
    {
        $host = $this->hostName();

        $result = $this->connector->ai()->dismissInsight($host, $uuid)->throw()->json();

        $this->forget($host, 'ai.dashboard');

        return $result;
    }

    // --- File Exports ---
    // Binary report exports on StatsResource. Callers must NOT call
    // ->json() on these - stream/save the raw response body instead.

    /**
     * @param  array<string, mixed>  $data
     */
    public function streamQueueReportFile(array $data): Response
    {
        $host = $this->hostName();

        return $this->connector->stats()->getQueueReportFile($host, $data)->throw();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function streamUserReportFile(array $data): Response
    {
        $host = $this->hostName();

        return $this->connector->stats()->getUserReportFile($host, $data)->throw();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function streamCallerReportFile(array $data): Response
    {
        $host = $this->hostName();

        return $this->connector->stats()->getCallerReportFile($host, $data)->throw();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function streamDidReportFile(array $data): Response
    {
        $host = $this->hostName();

        return $this->connector->stats()->getDidReportFile($host, $data)->throw();
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

    /**
     * Best-effort cache invalidation for a write: forgets the cache entry a
     * matching remember() read would have used. Callers pass the same
     * $keySuffix (and, if the read varies by query, the same $query) as the
     * read method(s) it could have staled.
     *
     * @param  array<string, mixed>  $query
     */
    private function forget(string $host, string $keySuffix, array $query = []): void
    {
        Cache::forget("expert-statistics.{$keySuffix}.{$host}.".md5(serialize($query)));
    }
}
