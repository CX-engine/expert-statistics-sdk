# Dashboard

The Dashboard is the fastest way to see how your phone activity is trending, without picking a
specific queue or user first. It has two tabs: **Period Analysis** and **12-Month Trends**.

## Period Analysis tab

Pick a period from the selector — **Today, Yesterday, This week, Last week, This month, Last
month, Last 3 months, Last 6 months** — and the whole tab updates.

**Total Calls widget** — shows total call count and total call duration, split into Inbound,
Outbound and Internal, with a note that external/internal classification depends on how your PBX
is configured.

**Inbound Calls widget** — two views, by queue and by user (extension), each showing:
- **Answered** vs **Unanswered** as stacked bars, switchable **By Hour** / **By Day**.
- **Lost calls** — calls that were never answered *and* never abandoned in the pre-answer stage
  (i.e. the caller hung up while genuinely waiting for an agent).
- **Average answer time** and **Short Calls** (answered within 10 seconds — these are usually
  either lucky fast pickups or accidental hang-ups, worth a second look if the count is high).
- A **"Multi-queue call consolidation"** toggle — when a caller's call touches more than one queue
  before being answered, this option counts it once instead of once per queue, so you see the real
  number of distinct incoming calls rather than an inflated per-queue count.

Three radial-gauge cards summarise the period at a glance: **Lost Calls**, **Average Answer Time**,
and **Short Calls (≤10s)** — each shown as a percentage of total calls, so you can eyeball how
healthy the period was without reading exact numbers.

**Outbound Calls widget** — same answered/unanswered breakdown for calls your team placed.

**Top 10 Users widget** — your busiest extensions for the period, with footnotes explaining exactly
how inbound, outbound and internal calls are attributed to each user (useful if a number looks
higher or lower than you expected).

## 12-Month Trends tab

Two trend lines, both showing the current month vs. the same month last year, or the longest run of
history available:

- **Answered Calls Trend** — combines Total Calls (bars), Answered Calls (line) and **Answer Rate
  %** (line) on one chart, so you can see volume and quality together.
- **Average Wait Time Trend** — a single line showing how long callers waited, month over month.

Above the charts, three comparison figures show this month vs. last month for **Total Calls**,
**Call Answer Rate**, and **Average Wait Time**, each with a trend arrow and percentage change.

## Insight cards

Below the main charts you'll see plain-language insight callouts, e.g. *"X out of Y calls were
lost"*, an explanation of what "lost calls" excludes (dissuaded/abandoned calls are counted
separately), and a note on what counts as a "short call" and why it's worth reviewing.

## Quick actions

At the top of the Dashboard:

- **Send report** — emails the current view once, to whoever you choose. See
  [Scheduled Reports & Sharing](11-scheduled-reports-and-sharing.md).
- **Schedule report** — sets up a recurring email of this view (daily/weekly/monthly).
- **View reports** — jumps to **Scheduled Reports** to manage existing schedules.
- **Create alert** — jumps you toward AI Alert configuration (see [AI Insights](09-ai-insights.md)).

> Note: Send/Schedule report requires `expert-statistics.modify` — see
> [Permissions & Access](02-permissions.md).

## Related pages

- For queue-level or user-level detail behind these numbers, see [My Queues](04-my-queues.md) and
  [My Users](05-my-users.md).
- For what each KPI actually means, see the [KPI & Report Glossary](13-kpi-and-report-glossary.md).
