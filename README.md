# cx-engine/expert-statistics

Dashboard and Expert Statistics (PBX call analytics) for BlueRockTEL CX Engine apps, ported from
`bluerocktelclients`'s Filament implementation into framework-agnostic services plus plain
Livewire v3 full-page components.

## What's included

- **`ExpertStatisticsService`** — a caching facade over
  [`cx-engine/expert-stats-api-sdk-php`](https://github.com/CX-engine/expert-stats-api-sdk-php)'s
  `StatsResource`, one method per report/KPI/trend endpoint.
- **`PbxDataProcessor`** — pure data-shaping/aggregation functions (KPI calculations, ApexCharts
  series builders, trend math), framework-free.
- **Livewire components** — `Dashboard` plus the My Queues / My Users / My Numbers / Caller
  Numbers report pages, each with their Blade views (ApexCharts via Alpine, no bundled JS
  dependency).
- **`ResolvesActivePbxHost`** — a one-method contract the host app implements to say which PBX
  host name Dashboard/Expert Statistics data should be fetched for (bluerocktelclients keys this
  off a session value; bluerocktel-cx resolves it from the active tenant's `Pbx3cxHost`).

## Not included

PBX host/configuration admin UI, wallboards, AI dashboard/alerts/chat, agent monitoring, scheduled
report emails, and CSV/file exports. These are heavier, more admin-oriented features layered on
the same backend and can be added later if needed.

## Installation

```bash
composer require cx-engine/expert-statistics
```

The package reuses the `XPSTAT_API_URL` / `XPSTAT_USERNAME` / `XPSTAT_PASSWORD` env vars already
used by `cx-engine/expert-stats-api-sdk-php` — no new credentials to provision if the host app
already talks to this API.

Bind `CXEngine\ExpertStatistics\Contracts\ResolvesActivePbxHost` in the host app's service
provider to say how to resolve the active PBX host for the current request/tenant.

## Development

This package depends on an unreleased branch of `cx-engine/expert-stats-api-sdk-php`
(`feature/stats-endpoints`) that adds the report/KPI/trend endpoints this package needs. Until
that branch is merged and tagged, consume both packages via local path repositories — see the
`repositories` block in a consuming app's `composer.json`.
