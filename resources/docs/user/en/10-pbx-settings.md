# PBX Settings

**PBX Settings** is a single tabbed page holding every piece of Expert Statistics configuration
that isn't PBX host management itself (host connection details are an administrator-only concern —
see the Reseller/Admin guide). Anyone with `expert-statistics.view` can open every tab and read its
current configuration; a read-only banner appears and every Save/Create/Delete control is disabled
or hidden unless you also have `expert-statistics.modify` (see
[Permissions & Access](02-permissions.md)).

## Active Host tab

If your account has more than one PBX host, this is where you choose which one all the report
pages currently query. Pick a host from the dropdown and click **Save**. This choice only affects
what you see when browsing reports — it doesn't change activation status or host configuration.

## Agent tab

Two free-text fields, **Custom 1** and **Custom 2**, let you rename the two custom agent statuses
shown throughout Agent Monitoring and My Users' Status tab (e.g. rename "Custom 1" to "Training" or
"Lunch"). Click **Save**.

## Queue tab

Configure **pre-answer time** — a duration (in seconds) applied to one or more queues, used to
adjust how "time to answer" is measured for those queues. Select the queues to apply it to, enter
the duration, and **Save**. Below the form:

- **Current** sub-tab — active pre-answer-time records (queue, seconds, who created it and when),
  each with a delete option; you can also select several and **Delete Selected**.
- **History** sub-tab — a searchable, paginated log of past pre-answer-time records.

## Report Table tab

Two groups of settings that control how report tables are computed and colour-coded:

- **Time Intervals** — Time Range (10–140s, the first bucket), Time Gap (10–40s, the size of each
  following bucket), and Columns (1–5, how many buckets to show). Together these define how call
  durations get bucketed in interval-style reports.
- **Visual Alert Thresholds** — for five metrics (Answer rate %, Average wait time – queue,
  Average wait time – user, Average call duration, Call solicitation ratio), set numeric thresholds
  for four colour levels (Red / Orange / Yellow / Green), each individually toggleable on/off, with
  **Activate all** / **Deactivate all** shortcuts per metric. These thresholds drive the colour
  coding you see on report tables throughout the module.

Click **Save** to apply.

## AI Alerts tab

Controls for the alerts described in [AI Insights](09-ai-insights.md#ai-alerts):

- **Enabled** — turn AI alerting on/off entirely.
- **Check interval** — how often (15–1440 minutes) the system re-evaluates alert conditions.
- **Language** and **Notification email** — where and in what language alert notifications go.
- **Thresholds** — Abandon rate (Warning/Critical %), Not-answered rate (Warning/Critical %),
  Pre-answer abandonment rate (Warning %), Wait time (Warning/Critical, seconds), and Change
  thresholds (Volume change % / Abandon-rate change %) — the sensitivity for detecting a sudden
  shift rather than a steady-state breach.

Click **Save** to apply.

## Groups tab

Group your queues, extensions, DID numbers, or caller numbers into named **Resource Groups**, so
you can report on or schedule reports for a meaningful set (e.g. "Sales team" extensions, or "Main
office" DIDs) instead of picking members one by one every time.

- Four sub-tabs: **Queues**, **Extensions**, **DID Numbers**, **Caller Numbers**.
- **+ New group** — name the group, then pick its members: a checkbox list for queues/extensions/
  DIDs, or a search-and-add-chip picker for caller numbers.
- Existing groups appear in a table with member chips (showing a few, with "+N" overflow), and
  per-row **Edit**/**Delete** actions.

Resource groups you create here become selectable when creating or editing a
[Scheduled Report](11-scheduled-reports-and-sharing.md).

## Wallboard tab

See [Wallboard & Live Data](12-wallboard-live-data.md) for the full walkthrough — this tab is
where wallboards get built, previewed, and shared.

## Related pages

- What each permission level lets you do on this page: [Permissions & Access](02-permissions.md).
- Using resource groups when scheduling a report: [Scheduled Reports & Sharing](11-scheduled-reports-and-sharing.md).
