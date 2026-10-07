# cx-engine/expert-statistics

Dashboard and Expert Statistics (PBX call analytics) for BlueRockTEL CX Engine apps, ported from
`bluerocktelclients`'s Filament implementation into framework-agnostic services plus plain
Livewire full-page components.

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

Supported framework lines: Laravel 11–13, Livewire 3–4, Filament 4–5.

### Reaching XP-Stats through a relay instead of the service account

By default the SDK connector logs in as the shared `XPSTAT_USERNAME` service account. A host app
that must not hold those credentials (e.g. a customer portal whose backend already proxies
XP-Stats per customer) rebinds the connector from its own service provider with a subclass that
points at its relay and authenticates as the current user:

```php
$this->app->scoped(ExpertStatisticsConnector::class, fn () => new RelayExpertStatisticsConnector(...));
```

The connector and `ExpertStatisticsService` are registered as `scoped`, so a per-user connector is
rebuilt for every request and queued job and never shared between users.

### Embedding the pages in the host's own pages

By default every page renders inside the host's `<x-pages.index>` component and the cluster pages
add a secondary navigation built from `<x-menus.item>` / `<x-menus.item-dropdown>`. A host that
wraps the components in its own pages and navigation (e.g. a Filament panel) sets
`EXPERT_STATISTICS_PAGE_SHELL=false` (config key `page_shell`): the components then render their
content only and the host needs none of those Blade components.

## Development

This package depends on an unreleased branch of `cx-engine/expert-stats-api-sdk-php`
(`feature/stats-endpoints`) that adds the report/KPI/trend endpoints this package needs. Until
that branch is merged and tagged, consume both packages via local path repositories — see the
`repositories` block in a consuming app's `composer.json`.
