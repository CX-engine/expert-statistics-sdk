<?php

namespace CXEngine\ExpertStatistics\Livewire\ConfigAudit;

use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\ChecksExpertStatisticsModifyPermission;
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
 *
 * Moving from a queue, an agent or a DID to its recommendations (and back
 * through the links of a recommendation) keeps the view left behind on a
 * stack: the "Back" bar returns to it. Marking a finding as expected for the
 * PBX requires expert-statistics.modify.
 */
class ConfigAudit extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use ChecksExpertStatisticsModifyPermission;
    use RequiresExpertStatisticsActivation;

    public const TABS = ['overview', 'recommendations', 'routing', 'queues', 'agents', 'history'];

    private const PER_PAGE = 20;

    private const MAX_BACK = 10;

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

    #[Url(as: 'q')]
    public string $findingSearch = '';

    /** '' (to review) | accepted (marked as expected for this PBX) */
    #[Url(as: 'status')]
    public string $findingStatus = '';

    public int $findingsPage = 1;

    /** @var array<string, mixed> */
    public array $findings = [];

    // Routing
    public string $routeSearch = '';

    public int $routesPage = 1;

    /** @var array<string, mixed> */
    public array $routes = [];

    #[Url(as: 'did')]
    public ?string $selectedDid = null;

    /** @var array<string, mixed> */
    public array $routeDetail = [];

    /** Times the route was asked again while its sheet was being summarised. */
    public int $routePolls = 0;

    private const MAX_ROUTE_POLLS = 15;

    // Queues, agents, history
    /** @var array<int, array<string, mixed>> */
    public array $queues = [];

    public string $agentSearch = '';

    public int $agentsPage = 1;

    /** @var array<string, mixed> */
    public array $agents = [];

    /** @var array<int, array<string, mixed>> */
    public array $history = [];

    /**
     * Views left for a related one, most recent last.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $backStack = [];

    /** Queue number or agent DN to open and scroll to in its tab. */
    public ?string $focus = null;

    public function mount(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'overview';
        }

        $this->loadSummary();
        $this->loadTab();
        if ($this->tab === 'routing' && $this->selectedDid !== null) {
            $this->showRoute($this->selectedDid);
        }
    }

    // ─── Actions ─────────────────────────────────────────────────────

    /**
     * Tab chosen in the tab bar: a new starting point, nothing to go back to.
     */
    public function selectTab(string $tab): void
    {
        $this->backStack = [];
        $this->focus = null;
        $this->openTab($tab);
    }

    /**
     * Return to the view left by the last "see related" link.
     */
    public function goBack(): void
    {
        $view = array_pop($this->backStack);
        if ($view === null) {
            return;
        }

        $this->findingCategory = $view['category'];
        $this->findingArea = $view['area'];
        $this->findingSearch = $view['search'];
        $this->findingStatus = $view['status'];
        $this->agentSearch = $view['agent_search'];
        $this->focus = $view['focus'];
        $this->findingsPage = $view['findings_page'];
        $this->agentsPage = $view['agents_page'];
        $this->openTab($view['tab']);

        if ($view['tab'] === 'routing' && $view['did'] !== null) {
            $this->showRoute($view['did']);
        } else {
            $this->closeRoute();
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

    public function updatedFindingStatus(): void
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

    public function showRoute(string $did, ?string $from = null): void
    {
        if ($from !== null) {
            $this->rememberView($from);
            $this->openTab('routing');
        }
        $this->selectedDid = $did;
        $this->routeDetail = [];
        $this->routePolls = 0;
        $this->loadRoute();
    }

    /**
     * Asks again while the API is still summarising the call flow sheet
     * (wire:poll in the routing tab, a minute at most).
     */
    public function refreshRoute(): void
    {
        if ($this->selectedDid === null || empty($this->routeDetail['sheet']['pending']) || $this->routePolls >= self::MAX_ROUTE_POLLS) {
            return;
        }
        $this->routePolls++;
        $this->loadRoute();
    }

    /**
     * Route of the selected DID. A failure (slow API, network) stays in the
     * routing tab with a retry, the rest of the page keeps working.
     */
    private function loadRoute(): void
    {
        try {
            $this->routeDetail = app(ExpertStatisticsService::class)->getConfigAuditRoute($this->selectedDid, $this->periodQuery());
        } catch (\Throwable) {
            $this->routeDetail = ['load_error' => true];
        }
    }

    public function closeRoute(): void
    {
        $this->selectedDid = null;
        $this->routeDetail = [];
    }

    /**
     * Open the recommendations filtered on a category and / or an area.
     */
    public function showFindings(string $category = '', string $area = '', string $status = ''): void
    {
        $this->rememberView();
        $this->findingCategory = $category;
        $this->findingArea = $area;
        $this->findingSearch = '';
        $this->findingStatus = $status === 'accepted' ? 'accepted' : '';
        $this->findingsPage = 1;
        $this->openTab('recommendations');
    }

    /**
     * Open the recommendations filtered on one object (queue, agent, DID).
     *
     * @param  string|null  $from  name of the object left (queue, agent), for the Back bar
     */
    public function showFindingsFor(string $search, ?string $from = null): void
    {
        $this->rememberView($from);
        $this->findingCategory = '';
        $this->findingArea = '';
        $this->findingSearch = $search;
        $this->findingStatus = '';
        $this->findingsPage = 1;
        $this->openTab('recommendations');
    }

    /**
     * Open the queues tab on one queue (from a recommendation, an agent, a route).
     */
    public function showQueue(string $number, ?string $from = null): void
    {
        $this->rememberView($from);
        $this->focus = $number;
        $this->openTab('queues');
    }

    /**
     * Open the agents tab on one agent, with their queues unfolded.
     */
    public function showAgent(string $dn, ?string $from = null): void
    {
        $this->rememberView($from);
        $this->focus = $dn;
        $this->agentSearch = $dn;
        $this->agentsPage = 1;
        $this->openTab('agents');
    }

    /**
     * Mark a finding as expected for this PBX: it no longer counts in the
     * score nor appears in the recommendations, for this audit and the next.
     */
    public function acceptFinding(string $findingId, string $note = ''): void
    {
        $this->ensureCanModify();

        $this->attempt(fn (ExpertStatisticsService $service) => $service->acceptConfigAuditFinding($findingId, trim($note) ?: null));
        $this->loadSummary();
        $this->loadTab();
    }

    /**
     * Report a finding marked as expected again.
     */
    public function revokeAcceptance(int $acceptanceId): void
    {
        $this->ensureCanModify();

        $this->attempt(fn (ExpertStatisticsService $service) => $service->revokeConfigAuditAcceptance($acceptanceId));
        $this->loadSummary();
        $this->loadTab();
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
                'status' => $this->findingStatus === 'accepted' ? 'accepted' : null,
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
     * Period and language of every audit read: the recommendations are
     * rendered in the user's language (en or fr) and the response cache,
     * keyed by the query, keeps the languages apart.
     *
     * @return array<string, string>
     */
    private function periodQuery(): array
    {
        $locale = in_array(app()->getLocale(), ['en', 'fr'], true) ? app()->getLocale() : 'en';

        return ($this->startDate && $this->endDate
            ? ['start_date' => $this->startDate, 'end_date' => $this->endDate]
            : []) + ['locale' => $locale];
    }

    private function openTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->tab = $tab;
            $this->loadTab();
        }
    }

    /**
     * Keep the current view so that the Back bar can return to it.
     */
    private function rememberView(?string $label = null): void
    {
        $tabName = __('expert-statistics::pbx.config_audit.tabs.'.$this->tab);

        $this->backStack[] = [
            'label' => $label ?? ($this->tab === 'routing' && $this->selectedDid !== null
                ? __('expert-statistics::pbx.config_audit.did_title', ['did' => $this->selectedDid])
                : $tabName),
            'tab' => $this->tab,
            'did' => $this->tab === 'routing' ? $this->selectedDid : null,
            'category' => $this->findingCategory,
            'area' => $this->findingArea,
            'search' => $this->findingSearch,
            'status' => $this->findingStatus,
            'agent_search' => $this->agentSearch,
            'focus' => $this->focus,
            'findings_page' => $this->findingsPage,
            'agents_page' => $this->agentsPage,
        ];
        $this->backStack = array_slice($this->backStack, -self::MAX_BACK);
        $this->focus = null;
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
