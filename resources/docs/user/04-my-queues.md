# My Queues

Everything about calls that went through a queue (a group of agents sharing incoming calls), with
four sub-pages: **Dashboard**, **Report**, **KPI**, and **Origins**.

## Choosing your queues

Every My Queues page starts with a queue/extension selector so you can pick which queues (and
optionally which individual extensions) to include, plus a date range.

## Dashboard

A condensed version of the main Dashboard, scoped to the queues you selected — same style of
answered/unanswered charts, radial-gauge summary cards, and the **Exclude closed hours** toggle
(see below).

## Report

A sortable, filterable table — one row per queue (or per queue+extension, if you disable "Queues
only"). Columns:

| Column | Meaning |
|---|---|
| **Date** | Shown when you're viewing "Day by day" instead of "Total period". |
| **Queue** / **Extension** | Which queue (and, if enabled, which extension) the row is for. |
| **Calls** | Total calls that reached this queue. |
| **Unanswered** | Split into *Declined* and *Abandoned* — declined calls were not answered by an agent; abandoned calls were hung up by the caller before being answered (the pre-answer-abandoned count appears in parentheses when non-zero). |
| **Internal** (queues-only view) or **Solicited** (extension view) | Internal calls into the queue, or calls specifically routed/solicited to that extension. |
| **Answered** | Calls actually picked up. |
| **Transferred** | Calls transferred elsewhere after being answered. |
| **Rate %** | Answer rate — colour-coded green (≥80%), yellow (≥60%), red (below), calculated as answered ÷ (calls − pre-answer-abandoned). |
| **Talk dur.** | Total talk time for that row. |
| **Wait dur.** | Total time callers spent waiting. |

### Row toggles

- **Queues only (no extensions)** — collapse the table to one row per queue instead of per
  queue+extension.
- **Exclude closed hours** — filters out calls that happened during the PBX's configured closed/off
  hours, so a queue that's only staffed 9–5 doesn't get penalised by after-hours call volume.
- **Consolidate** — (queues-only view) merges all selected queues into a single combined
  "Consolidated" row, useful when you want one total instead of a per-queue breakdown.
- **Multi-queue call consolidation** — when a single caller's call passes through more than one
  queue before being answered, this counts it once instead of once per queue it touched, giving
  you the real number of distinct incoming calls. This is different from the "Consolidate" row
  toggle above — one collapses *rows*, the other de-duplicates *calls*. You can use either
  independently, or both together.
- Per-column text filters let you search by queue or extension name.

## KPI

Summary cards (**Total calls, Answered, Unanswered, Rate %, Average wait**) plus a chart per
selected queue/extension, and one **Consolidated** chart summing all of them. Choose a
**granularity**: consolidated by hour, consolidated by weekday, or an evolution view by day, week,
or month. Each chart combines an Answered/Unanswered stacked bar with an Answer Rate % line, and —
when wait-time data exists — a separate Average Wait Time line chart underneath.

## Origins

A donut chart breaking down where calls came from: Internal, External, another Queue, Voicemail,
IVR, or Call Flow — or, alternatively, a breakdown by the top 10 originating numbers/extensions.
Useful for understanding *why* a queue is receiving the volume it is.

## Exporting

Report, Dashboard, KPI and Origins pages each have an export action that produces an Excel (.xlsx)
file of the current view, respecting whatever filters (date range, closed-hours exclusion, etc.)
are currently applied.

## Related pages

- For the same breakdown by individual agent instead of queue, see [My Users](05-my-users.md).
- To email this view once or on a schedule, see
  [Scheduled Reports & Sharing](11-scheduled-reports-and-sharing.md).
- Column and KPI definitions: [KPI & Report Glossary](13-kpi-and-report-glossary.md).
