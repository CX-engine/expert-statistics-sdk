<?php

namespace CXEngine\ExpertStatistics\Livewire\ConfigAudit;

use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\RequiresExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Exceptions\AiFeaturesNotActivatedException;
use CXEngine\ExpertStatistics\Exceptions\ConfigAuditNotAvailableException;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 3CX configuration audit: configuration score (share of calls handled
 * without a configuration problem), recommendations with the 3CX setting to
 * change, DID routing, queues and agents, crossed with the call statistics
 * of a period (default: last 30 days on the server).
 *
 * The overview is loaded on mount; each other tab loads its own endpoint
 * when it is opened. Same access rules as the other AI pages: Expert
 * Statistics activation in the app, AI features activated for the host on
 * the backend (403 → "not activated" state).
 */
class ConfigAudit extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use RequiresExpertStatisticsActivation;

    public const TABS = ['overview', 'recommendations', 'routing', 'queues', 'agents', 'history'];

    private const PER_PAGE = 20;

    #[Url]
    public string $tab = 'overview';

    #[Url(as: 'from')]
    public ?string $startDate = null;

    #[Url(as: 'to')]
    public ?string $endDate = null;

    /** ok | empty (no audit yet) | not_activated (AI off for the host) | error */
    public string $state = 'ok';

    public ?string $errorMessage = null;

    /** @var array<string, mixed> */
    public array $summary = [];

    // Recommendations
    #[Url(as: 'category')]
    public string $findingCategory = '';

    #[Url(as: 'area')]
    public string $findingArea = '';

    public string $findingSearch = '';

    public int $findingsPage = 1;

    /** @var array<string, mixed> */
    public array $findings = [];

    // Routing
    public string $routeSearch = '';

    public int $routesPage = 1;

    /** @var array<string, mixed> */
    public array $routes = [];

    public ?string $selectedDid = null;

    /** @var array<string, mixed> */
    public array $routeDetail = [];

    // Queues, agents, history
    /** @var array<int, array<string, mixed>> */
    public array $queues = [];

    public string $agentSearch = '';

    public int $agentsPage = 1;

    /** @var array<string, mixed> */
    public array $agents = [];

    /** @var array<int, array<string, mixed>> */
    public array $history = [];

    public function mount(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'overview';
        }

        $this->loadSummary();
        $this->loadTab();
    }

    // ─── Actions ─────────────────────────────────────────────────────

    public function selectTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->tab = $tab;
            $this->loadTab();
        }
    }

    public function applyPeriod(): void
    {
        if (($this->startDate === null) !== ($this->endDate === null) || ($this->startDate && $this->endDate && $this->startDate > $this->endDate)) {
            $this->addError('period', __('expert-statistics::pbx.config_audit.period_invalid'));

            return;
        }

        $this->resetErrorBag('period');
        $this->resetPages();
        $this->loadSummary();
        $this->loadTab();
    }

    public function resetPeriod(): void
    {
        $this->startDate = null;
        $this->endDate = null;
        $this->applyPeriod();
    }

    public function refresh(): void
    {
        $this->attempt(fn (ExpertStatisticsService $service) => $service->refreshConfigAudit());
        $this->loadSummary();
        $this->loadTab();
    }

    public function downloadReport(): ?StreamedResponse
    {
        $report = null;
        $this->attempt(function (ExpertStatisticsService $service) use (&$report) {
            $report = $service->getConfigAuditReport();
        });

        if ($report === null) {
            return null;
        }

        $date = $this->summary['audit']['backup_date'] ?? null;
        $name = 'config-audit-'.($date ? \Carbon\Carbon::parse($date)->toDateString() : now()->toDateString()).'.md';

        return response()->streamDownload(function () use ($report) {
            echo $report;
        }, $name, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    public function updatedFindingCategory(): void
    {
        $this->findingsPage = 1;
        $this->loadFindings();
    }

    public function updatedFindingArea(): void
    {
        $this->findingsPage = 1;
        $this->loadFindings();
    }

    public function updatedFindingSearch(): void
    {
        $this->findingsPage = 1;
        $this->loadFindings();
    }

    public function updatedRouteSearch(): void
    {
        $this->routesPage = 1;
        $this->loadRoutes();
    }

    public function updatedAgentSearch(): void
    {
        $this->agentsPage = 1;
        $this->loadAgents();
    }

    public function goToPage(string $list, int $page): void
    {
        $page = max(1, $page);

        switch ($list) {
            case 'findings':
                $this->findingsPage = $page;
                $this->loadFindings();
                break;
            case 'routes':
                $this->routesPage = $page;
                $this->loadRoutes();
                break;
            case 'agents':
                $this->agentsPage = $page;
                $this->loadAgents();
                break;
        }
    }

    public function showRoute(string $did): void
    {
        $this->selectedDid = $did;
        $this->routeDetail = [];
        $this->attempt(function (ExpertStatisticsService $service) use ($did) {
            $this->routeDetail = $service->getConfigAuditRoute($did, $this->periodQuery());
        });
    }

    public function closeRoute(): void
    {
        $this->selectedDid = null;
        $this->routeDetail = [];
    }

    /**
     * Open the recommendations filtered on a category and / or an area.
     */
    public function showFindings(string $category = '', string $area = ''): void
    {
        $this->findingCategory = $category;
        $this->findingArea = $area;
        $this->findingSearch = '';
        $this->findingsPage = 1;
        $this->selectTab('recommendations');
    }

    /**
     * Open the recommendations filtered on one object (queue, agent, DID).
     */
    public function showFindingsFor(string $search): void
    {
        $this->findingCategory = '';
        $this->findingArea = '';
        $this->findingSearch = $search;
        $this->findingsPage = 1;
        $this->selectTab('recommendations');
    }

    // ─── View helpers ────────────────────────────────────────────────

    public function scoreColor(?float $score): string
    {
        return match (true) {
            $score === null => 'text-gray-400',
            $score >= 90 => 'text-emerald-600 dark:text-emerald-400',
            $score >= 70 => 'text-amber-500 dark:text-amber-400',
            default => 'text-red-600 dark:text-red-400',
        };
    }

    public function scoreBarColor(?float $score): string
    {
        return match (true) {
            $score === null => 'bg-gray-300',
            $score >= 90 => 'bg-emerald-500',
            $score >= 70 => 'bg-amber-400',
            default => 'bg-red-500',
        };
    }

    public function categoryBadge(string $category): string
    {
        return match ($category) {
            'probable_error' => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-950/30 dark:text-red-400 dark:ring-red-900',
            'to_check' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:ring-amber-900',
            default => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950/30 dark:text-sky-400 dark:ring-sky-900',
        };
    }

    public function codeLabel(string $code): string
    {
        $key = "expert-statistics::pbx.config_audit.codes.{$code}";
        $label = __($key);

        return $label === $key ? ucfirst(str_replace('_', ' ', $code)) : $label;
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.config-audit.index');
    }

    // ─── Loading ─────────────────────────────────────────────────────

    private function loadSummary(): void
    {
        $this->state = 'ok';
        $this->errorMessage = null;
        $this->attempt(function (ExpertStatisticsService $service) {
            $this->summary = $service->getConfigAudit($this->periodQuery());
        });
    }

    private function loadTab(): void
    {
        if ($this->state !== 'ok') {
            return;
        }

        match ($this->tab) {
            'recommendations' => $this->loadFindings(),
            'routing' => $this->loadRoutes(),
            'queues' => $this->attempt(function (ExpertStatisticsService $service) {
                $this->queues = $service->getConfigAuditQueues($this->periodQuery())['data'] ?? [];
            }),
            'agents' => $this->loadAgents(),
            'history' => $this->attempt(function (ExpertStatisticsService $service) {
                $this->history = $service->getConfigAuditHistory()['data'] ?? [];
            }),
            default => null,
        };
    }

    private function loadFindings(): void
    {
        $this->attempt(function (ExpertStatisticsService $service) {
            $this->findings = $service->getConfigAuditFindings(array_filter([
                ...$this->periodQuery(),
                'category' => $this->findingCategory !== '' ? [$this->findingCategory] : null,
                'area' => $this->findingArea !== '' ? [$this->findingArea] : null,
                'search' => trim($this->findingSearch) ?: null,
                'page' => $this->findingsPage,
                'per_page' => self::PER_PAGE,
            ], fn ($v) => $v !== null));
        });
    }

    private function loadRoutes(): void
    {
        $this->attempt(function (ExpertStatisticsService $service) {
            $this->routes = $service->getConfigAuditRoutes(array_filter([
                ...$this->periodQuery(),
                'search' => trim($this->routeSearch) ?: null,
                'page' => $this->routesPage,
                'per_page' => self::PER_PAGE,
            ], fn ($v) => $v !== null));
        });
    }

    private function loadAgents(): void
    {
        $this->attempt(function (ExpertStatisticsService $service) {
            $this->agents = $service->getConfigAuditAgents(array_filter([
                ...$this->periodQuery(),
                'search' => trim($this->agentSearch) ?: null,
                'page' => $this->agentsPage,
                'per_page' => self::PER_PAGE,
            ], fn ($v) => $v !== null));
        });
    }

    /**
     * Run a service call and turn the known failures into page states.
     */
    private function attempt(\Closure $call): void
    {
        try {
            $call(app(ExpertStatisticsService::class));
        } catch (ConfigAuditNotAvailableException) {
            $this->state = 'empty';
        } catch (AiFeaturesNotActivatedException) {
            $this->state = 'not_activated';
        } catch (\Throwable) {
            $this->state = 'error';
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
        }
    }

    /**
     * @return array<string, string>
     */
    private function periodQuery(): array
    {
        return $this->startDate && $this->endDate
            ? ['start_date' => $this->startDate, 'end_date' => $this->endDate]
            : [];
    }

    private function resetPages(): void
    {
        $this->findingsPage = 1;
        $this->routesPage = 1;
        $this->agentsPage = 1;
        $this->selectedDid = null;
        $this->routeDetail = [];
    }
}
