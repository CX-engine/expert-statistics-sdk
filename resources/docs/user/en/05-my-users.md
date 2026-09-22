# My Users

The same reporting depth as [My Queues](04-my-queues.md), but scoped to individual users
(extensions/agents) instead of queues. Four sub-pages: **Dashboard**, **Report**, **KPI**, and
**Origins**, all following the same pattern — pick your users and a date range first.

## Report

The Report page has three tabs:

### Calls tab

Columns are grouped by call direction — **Outbound**, **Inbound**, **Internal**, **Total** — and
within each group you'll see:

- **Duration (total / average)** — how long, and how long on average per call.
- **Unanswered** — calls this user didn't pick up.
- **Wait (average)** — only meaningful for inbound: how long callers waited before this user
  answered.
- **Count** — number of calls.

Hover any column header for a tooltip explaining exactly what it counts — for example, "Inbound
answer rate" or "Average wait duration" are spelled out precisely so there's no ambiguity between
similarly-named columns across the Outbound/Inbound/Internal groups.

### Status tab

Two groups:

- **Connections** — Login time, Logout time, Total connection duration for the period.
- **Time distribution** — how the user's logged-in time split across **Available**, **Away**, **Do
  not disturb**, **Custom 1**, **Custom 2** (your admin can rename these two custom statuses — see
  [PBX Settings](10-pbx-settings.md#agent-tab)), and **Available out of call** — this last one is
  Available-status time *minus* time actually spent on a call, i.e. genuinely idle-and-ready time.

### Queues tab

Which queues this user took calls from/for, during the period.

## Dashboard, KPI, Origins

Structurally identical to the equivalent [My Queues](04-my-queues.md) pages, scoped to users
instead of queues — same radial-gauge summary cards, same KPI granularity options (hour / weekday
/ day / week / month), same call-origin donut chart.

## Shared toggles

- **Exclude closed hours** — available on every My Users sub-page, same behaviour as in My Queues.
- Export to Excel is available on every sub-page.

## Related pages

- Queue-level equivalent: [My Queues](04-my-queues.md).
- Live/current agent presence rather than historical reporting: [Agent Monitoring](08-agent-monitoring.md).
- Column definitions: [KPI & Report Glossary](13-kpi-and-report-glossary.md).
