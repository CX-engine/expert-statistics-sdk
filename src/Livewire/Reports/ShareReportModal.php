<?php

namespace CXEngine\ExpertStatistics\Livewire\Reports;

use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

/**
 * Report share/schedule modal, ported from bluerocktelclients'
 * App\Livewire\Dashboard\ShareReportModal and App\Livewire\ExpertStatistics\ShareReportModal,
 * merged into one reusable component — this package's Dashboard and report pages already
 * share a single component tree, unlike bluerocktelclients (which kept a Filament-page-per-
 * cluster split and therefore two near-duplicate modals).
 *
 * A "schedule" is nothing more than a report record with repeat=1 + repeat_pattern on the
 * remote `reports` resource — cron dispatch and the actual email sending are handled entirely
 * server-side. This component is CRUD-only: it builds a payload and calls
 * ExpertStatisticsService::createReport().
 *
 * Two modes, selected by whether $urlType is null:
 * - Dashboard mode ($urlType === null): mirrors Dashboard/ShareReportModal — report_type is
 *   always 'dashboard', element_type/dns are always '*', no element/resource-group UI.
 * - Report-page mode ($urlType !== null, one of 'queue'|'did'|'caller'|'extension'): mirrors
 *   ExpertStatistics/ShareReportModal — report_type/element_type are derived from $urlType,
 *   and the element/resource-group selection UI is shown (except for 'caller', which has no
 *   resource-group concept and shows the caller filter summary passed in via $callerFilters
 *   instead — that summary is for display only, it is not sent in the submit payload, matching
 *   the source component).
 */
class ShareReportModal extends Component
{
    public bool $isOpen = false;

    public bool $isSchedule = false;

    public bool $isLoading = false;

    // success|error|warning
    public ?string $statusType = null;

    public ?string $statusMessage = null;

    public string $nickname = '';

    public string $email = '';

    public bool $invalidEmail = false;

    public bool $emailAlreadyExists = false;

    /** @var array<int, string> */
    public array $emails = [];

    // day|week|month
    public string $cron = 'day';

    public string $startAt = '';

    public string $pbx3cx_host_resource_group_id = '';

    // ── Context received from the triggering page ───────────────────────────

    /** Null means "dashboard mode" — one of 'queue'|'did'|'caller'|'extension' otherwise. */
    public ?string $urlType = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $startTime = '07:00';

    public string $endTime = '19:00';

    public string $elements = '';

    /** @var array<string, mixed> Caller-numbers filter context (all_groups / group_names / queues / groupless) — display only. */
    public array $callerFilters = [];

    /** @var array<int, array<string, mixed>> */
    public array $resourceGroups = [];

    #[On('open-share-report')]
    public function openShare(
        ?string $urlType = null,
        ?string $startDate = null,
        ?string $endDate = null,
        string $startTime = '07:00',
        string $endTime = '19:00',
        string $elements = '',
        array $callerFilters = [],
    ): void {
        $this->isSchedule = false;
        $this->openModal($urlType, $startDate, $endDate, $startTime, $endTime, $elements, $callerFilters);
    }

    #[On('open-schedule-report')]
    public function openSchedule(
        ?string $urlType = null,
        ?string $startDate = null,
        ?string $endDate = null,
        string $startTime = '07:00',
        string $endTime = '19:00',
        string $elements = '',
        array $callerFilters = [],
    ): void {
        $this->isSchedule = true;
        $this->openModal($urlType, $startDate, $endDate, $startTime, $endTime, $elements, $callerFilters);
    }

    public function addEmail(): void
    {
        $trimmed = trim($this->email);
        $this->invalidEmail = false;
        $this->emailAlreadyExists = false;

        if (! filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            $this->invalidEmail = true;

            return;
        }

        if (in_array($trimmed, $this->emails, true)) {
            $this->invalidEmail = true;
            $this->emailAlreadyExists = true;

            return;
        }

        $this->emails[] = $trimmed;
        $this->email = '';
    }

    public function removeEmail(string $email): void
    {
        $this->emails = array_values(array_filter($this->emails, fn (string $e): bool => $e !== $email));
    }

    public function submit(): void
    {
        $this->statusType = null;
        $this->statusMessage = null;

        if (empty($this->emails)) {
            $this->flash('warning', $this->isDashboardMode() ? 'dashboards.report_no_recipients' : 'expert_statistics.report_no_recipients');

            return;
        }

        $this->isLoading = true;

        try {
            $payload = $this->isDashboardMode() ? $this->buildDashboardPayload() : $this->buildReportPayload();

            app(ExpertStatisticsService::class)->createReport($payload);

            $this->flash('success', $this->isDashboardMode()
                ? ($this->isSchedule ? 'dashboards.report_scheduled' : 'dashboards.report_sent')
                : ($this->isSchedule ? 'expert_statistics.report_scheduled' : 'expert_statistics.report_sent'));

            $this->isOpen = false;
        } catch (Throwable) {
            $this->flash('error', $this->isDashboardMode() ? 'dashboards.report_error' : 'expert_statistics.report_error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.reports.share-report-modal');
    }

    private function openModal(
        ?string $urlType,
        ?string $startDate,
        ?string $endDate,
        string $startTime,
        string $endTime,
        string $elements,
        array $callerFilters,
    ): void {
        $this->urlType = $urlType;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->elements = $elements;
        $this->callerFilters = $callerFilters;

        $this->resetForm();

        if (! $this->isDashboardMode()) {
            $this->loadResourceGroups();
        }

        $this->isOpen = true;
    }

    private function isDashboardMode(): bool
    {
        return $this->urlType === null;
    }

    private function resetForm(): void
    {
        $this->nickname = '';
        $this->email = '';
        $this->invalidEmail = false;
        $this->emailAlreadyExists = false;
        $this->emails = [];
        $this->cron = 'day';
        $this->startAt = now()->format('Y-m-d');
        $this->pbx3cx_host_resource_group_id = '';
        $this->statusType = null;
        $this->statusMessage = null;

        $user = Auth::user();
        if ($user?->email && ! in_array($user->email, $this->emails, true)) {
            $this->emails[] = $user->email;
        }
    }

    private function loadResourceGroups(): void
    {
        // Caller numbers has no resource-group concept.
        if ($this->urlType === 'caller') {
            $this->resourceGroups = [];

            return;
        }

        try {
            $elementTypeInt = $this->getElementTypeInt();
            $groups = app(ExpertStatisticsService::class)->getResourceGroups();
            $this->resourceGroups = collect($groups)
                ->filter(fn (array $g): bool => ($g['type'] ?? null) == $elementTypeInt)
                ->values()
                ->all();
        } catch (Throwable) {
            $this->resourceGroups = [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboardPayload(): array
    {
        $host = app(ExpertStatisticsService::class)->hostName();
        $user = Auth::user();

        $startBase = $this->isSchedule ? $this->startAt : ($this->startDate ?? '');
        $defaultName = __('expert-statistics::pbx.dashboards.defaultReportName').' - '.(
            $this->isSchedule ? $this->cronLabel() : trim(($this->startDate ?? '').' - '.($this->endDate ?? ''))
        );

        return [
            'host_name' => $host,
            'name' => $this->nickname !== '' ? $this->nickname : $defaultName,
            'report_type' => 'dashboard',
            'element_type' => '*',
            'start' => trim($startBase.' '.$this->startTime),
            'end' => trim(($this->endDate ?? '').' '.$this->endTime),
            'start_at' => trim($startBase.' '.$this->startTime),
            'email' => implode(',', $this->emails),
            'repeat' => $this->isSchedule,
            'repeat_pattern' => $this->isSchedule ? $this->cron : null,
            'dns' => '*',
            'instant' => false,
            'email_sender' => $user?->email,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportPayload(): array
    {
        $host = app(ExpertStatisticsService::class)->hostName();
        $user = Auth::user();

        $dataset = $this->getDataset();
        $elementTypeInt = $this->getElementTypeInt();
        $dns = $this->pbx3cx_host_resource_group_id !== '' ? null : ($this->elements !== '' ? $this->elements : null);
        $startBase = $this->isSchedule ? $this->startAt : ($this->startDate ?? '');

        return [
            'host_name' => $host,
            'name' => $this->buildReportName($dataset),
            'report_type' => $dataset,
            // Ported as-is from ExpertStatistics/ShareReportModal::getElementTypeInt(): any
            // urlType not explicitly mapped (i.e. 'extension') resolves to 0, which this
            // `?: '*'` then rewrites to '*' because 0 is falsy in PHP — kept for payload
            // parity with the source app rather than "fixed" here.
            'element_type' => $elementTypeInt ?: '*',
            'start' => trim($startBase.' '.$this->startTime),
            'end' => trim(($this->endDate ?? '').' '.$this->endTime),
            'start_at' => trim($startBase.' '.$this->startTime),
            'email' => implode(',', $this->emails),
            'repeat' => $this->isSchedule,
            'repeat_pattern' => $this->isSchedule ? $this->cron : null,
            'dns' => $dns,
            'instant' => false,
            'email_sender' => $user?->email,
            'pbx3cx_host_resource_group_id' => $this->pbx3cx_host_resource_group_id !== '' ? (int) $this->pbx3cx_host_resource_group_id : null,
        ];
    }

    private function getDataset(): string
    {
        return match ($this->urlType) {
            'did' => 'didReport',
            'queue' => 'report',
            'caller' => 'callerNumbersReport',
            'extension' => 'userReport',
            default => 'didReport',
        };
    }

    private function getElementTypeInt(): int
    {
        return match ($this->urlType) {
            'did', 'queue' => 4,
            'caller' => 99,
            default => 0,
        };
    }

    private function buildReportName(string $dataset): string
    {
        if ($this->nickname !== '') {
            return $this->nickname;
        }

        $label = match ($dataset) {
            'didReport' => __('expert-statistics::pbx.expert_statistics.my_numbers_title'),
            'report' => __('expert-statistics::pbx.expert_statistics.my_queues_title'),
            'callerNumbersReport' => __('expert-statistics::pbx.expert_statistics.caller_numbers_title'),
            'userReport' => __('expert-statistics::pbx.expert_statistics.my_users_title'),
            default => 'Report',
        };

        if ($this->isSchedule) {
            return $label.' - '.$this->cronLabel();
        }

        return $label.' - '.($this->startDate ?? '').' / '.($this->endDate ?? '');
    }

    private function cronLabel(): string
    {
        return match ($this->cron) {
            'day' => __('expert-statistics::pbx.dashboards.daily'),
            'week' => __('expert-statistics::pbx.dashboards.weekly'),
            default => __('expert-statistics::pbx.dashboards.monthly'),
        };
    }

    private function flash(string $type, string $key): void
    {
        $this->statusType = $type;
        $this->statusMessage = __("expert-statistics::pbx.{$key}");
    }
}
