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
| **Date** / **Period** | Shown in the Day, Week and Month views (see [Period views](#period-views-and-comparison)). |
| **Queue** / **Extension** | Which queue (and, if enabled, which extension) the row is for. |
| **Calls** | Total calls that reached this queue. |
| **Unanswered** | Split into *Declined* and *Abandoned* — declined calls were not answered by an agent; abandoned calls were hung up by the caller before being answered (the pre-answer-abandoned count appears in parentheses when non-zero). |
| **Internal** (queues-only view) or **Solicited** (extension view) | Internal calls into the queue, or calls specifically routed/solicited to that extension. |
| **Answered** | Calls actually picked up. |
| **Transferred** | Calls transferred elsewhere after being answered. |
| **Rate %** | Answer rate — colour-coded green (≥80%), yellow (≥60%), red (below), calculated as answered ÷ (calls − pre-answer-abandoned). |
| **Avg talk** | Average talk time per answered call. |
| **Avg wait** | Average time a caller waited in the queue before being answered, per call that waited, **pre-recorded message excluded** (calls that waited here and overflowed to another queue count here too). For an extension row: average ringing time before that agent answered. |

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

### Period views and comparison

Over a long range, a day-by-day table is hard to read. The **Total period · Day · Week · Month**
switch above the table chooses how the range is cut:

- **Total period** — one row per queue (or queue+extension) for the whole range.
- **Day** — one row per day (ranges of more than one day).
- **Week** — one row per ISO week, Monday to Sunday, labelled like *W10 · 03/02 – 03/08* (ranges of
  more than 7 days covering at least two weeks).
- **Month** — one row per calendar month (ranges of 28 days or more covering at least two months).

Views that don't make sense for the selected dates are greyed out; if you shorten the range so the
current view no longer fits, the report goes back to **Total period**. In the Week and Month views
the rows are grouped under a header per period, and sorting a column sorts inside each period. The
first and last periods are cut to your dates: such a *partial* period is marked with an asterisk —
hover it to see the days it covers.

**Spot anomalies** (off by default — tick it when you want the analysis) compares each period with
the **usual level** of its queue, taken from the **other selected periods**: with 6 months
selected, March is compared with the 5 other months. In the **Day** view, a day is compared with
the same weekday (a Monday with the other Mondays), since traffic depends on the weekday. With at
least three reference periods, the usual level is their median: a single bad period does not skew
the reference of the others.

Only what matters to your callers is judged: the **answer rate**, the **average wait** and
**abandoned** calls. The number of calls is not judged; it is given as the likely cause ("35 %
more calls than usual").

- A **Points of attention** panel above the table lists the queues and periods clearly **worse
  than usual**, one line per queue and period, the most important first (queues before
  extensions): for example *Answer rate 72.0 % (usually 88.0 %) · Average wait 1 min 40 s
  (usually 35 s) · 35 % more calls than usual*. It shows five at first — **Show all** expands the
  list. When nothing is abnormal, it says so.
- In the table, only an abnormal value gets a note under the figure, for example *▼ usually
  88.0 %*: **red** when worse than usual, **green** when better. A bold note is a **major** gap.
  Values within the usual range have none.
- Volumes are scaled to the period's length (a partial month, February vs March).
- Small volumes are not judged: a queue with 3 calls is not flagged.
- In the queues-only view, only queues are listed.

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

From the Report page in the Day, Week or Month view, the Excel file also contains a **report per
day/week/month** sheet and, when comparison is on, a **comparison** sheet listing the points
of attention first, then every period with its usual level, coloured like the screen.

## Related pages

- For the same breakdown by individual agent instead of queue, see [My Users](05-my-users.md).
- To email this view once or on a schedule, see
  [Scheduled Reports & Sharing](11-scheduled-reports-and-sharing.md).
- Column and KPI definitions: [KPI & Report Glossary](13-kpi-and-report-glossary.md).
