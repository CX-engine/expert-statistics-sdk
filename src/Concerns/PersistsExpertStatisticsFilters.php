<?php

namespace CXEngine\ExpertStatistics\Concerns;

/**
 * Persists expert-statistics filter state (period, time window, element selection)
 * across page navigations via the PHP session.
 *
 * Rules:
 * - Period + time window are shared across ALL expert-statistics pages.
 * - Element selection is scoped by urlType so that queue selections survive
 *   MyQueues navigations and extension selections survive MyUsers navigations,
 *   but the two never bleed into each other.
 *
 * Usage:
 * 1. Include this trait (directly or via HasPbxElementSelector).
 * 2. Call `$this->restoreExpertStatsFilters()` as the FIRST line of `mount()`,
 *    before `applyPeriod()`.
 * 3. Call `$this->saveExpertStatsPeriod()` whenever selectedPeriod / dates change.
 * 4. Call `$this->saveExpertStatsTime()` whenever startTime / endTime change.
 * 5. Call `$this->saveExpertStatsElements()` whenever the `dn` property changes.
 */
trait PersistsExpertStatisticsFilters
{
    /**
     * Restore filter state from session when no URL query parameters are present.
     * Must be called before applyPeriod() in mount().
     */
    public function restoreExpertStatsFilters(): void
    {
        if (! request()->has('selectedPeriod')) {
            $saved = session('expert_stats.period');
            if ($saved !== null) {
                $this->selectedPeriod = (string) $saved;
            }
        }

        if (! request()->has('startDate')) {
            $saved = session('expert_stats.start_date');
            if ($saved !== null) {
                $this->startDate = (string) $saved;
            }
        }

        if (! request()->has('endDate')) {
            $saved = session('expert_stats.end_date');
            if ($saved !== null) {
                $this->endDate = (string) $saved;
            }
        }

        if (! request()->has('startTime')) {
            $saved = session('expert_stats.start_time');
            if ($saved !== null) {
                $this->startTime = (string) $saved;
            }
        }

        if (! request()->has('endTime')) {
            $saved = session('expert_stats.end_time');
            if ($saved !== null) {
                $this->endTime = (string) $saved;
            }
        }

        // Element (dn) restoration: only for tracked types
        $urlType = $this->urlType ?? null;
        if ($urlType !== null && in_array($urlType, ['queue', 'extension'], true) && ! request()->has('dn')) {
            $saved = session("expert_stats.dn.{$urlType}");
            if ($saved !== null && $saved !== '') {
                $this->dn = (string) $saved;
            }
        }
    }

    /** Save the current period + date range to session. */
    protected function saveExpertStatsPeriod(): void
    {
        session([
            'expert_stats.period' => $this->selectedPeriod,
            'expert_stats.start_date' => $this->startDate,
            'expert_stats.end_date' => $this->endDate,
        ]);
    }

    /** Save the current time window to session. */
    protected function saveExpertStatsTime(): void
    {
        session([
            'expert_stats.start_time' => $this->startTime,
            'expert_stats.end_time' => $this->endTime,
        ]);
    }

    /**
     * Save the current element (dn) selection to session, keyed by urlType.
     * Only persists queue and extension types; others are silently skipped.
     */
    protected function saveExpertStatsElements(): void
    {
        $urlType = $this->urlType ?? null;
        if ($urlType === null || ! in_array($urlType, ['queue', 'extension'], true)) {
            return;
        }

        session(["expert_stats.dn.{$urlType}" => $this->dn ?? '']);
    }
}
