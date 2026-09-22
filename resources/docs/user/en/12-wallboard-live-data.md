# Wallboard & Live Data

A **Wallboard** is a live, auto-refreshing display of your call centre's current status — built to
be left open on an office screen or TV so anyone in the room can see queue activity at a glance.
Everything happens from **PBX Settings → Wallboard tab**.

> Requires `expert-statistics.modify` to build or edit a wallboard — anyone with `.view` can still
> preview an existing one. See [Permissions & Access](02-permissions.md).

## Building a wallboard

Two ways to create one:

### Build with AI

Describe what you want in plain English in the prompt box (a few example prompts are offered as
quick-fill chips), click **Generate**, and the AI proposes a wallboard layout. Review the result,
then **Discard** it or **Save** it to keep it.

### Manual editor

Click **New** (or **Edit** on an existing wallboard) and set:

- **Name** and **Description**.
- **Columns** — how many tiles wide the layout is (1–6).
- **Refresh** — how often it refreshes, in seconds (2–300).
- **Tiles** — add cards one at a time, each showing a chosen **Metric** (see the table below),
  optionally filtered to a specific queue. Remove a tile with its own control.

Click **Save** when done.

### Available metrics

| Metric | Meaning |
|---|---|
| Calls waiting | Calls currently in queue, not yet answered. |
| Calls in service | Calls currently being handled. |
| Agents busy / Agents available | Live agent headcount by state. |
| Longest wait | The longest any caller is currently waiting. |
| Longest call | The longest call currently in progress. |
| Total calls / Answered calls / Calls abandoned | Running counts for the period shown. |
| No agents | Time or count with zero agents available. |
| Talk time | Cumulative talk time. |
| Avg wait / Max wait | Average and peak wait time. |
| Avg handle time | Average total handling time per call. |
| Service level | Percentage of calls answered within your target time. |
| Abandon rate | Percentage of calls abandoned by the caller. |
| Queue alert level | A green/orange/red health indicator for the queue. |

## Managing existing wallboards

The wallboard list shows each one's name (with "Default" or "AI" badges where relevant), an
Active/Inactive status toggle, and per-row actions:

- **Preview** — see it rendered without leaving the settings page.
- **Copy link** — copies the public URL to your clipboard.
- **Open** — opens that public URL in a new tab, exactly as it will appear on a TV/monitor.
- **Edit** (modify permission required) — reopen the manual editor.
- **Revert** (default wallboard only) or **Delete** (other wallboards) — restore the default
  layout, or remove a custom one.

A live preview of whichever wallboard you're currently viewing sits at the top of the tab,
refreshing automatically.

## Displaying a wallboard publicly

Click **Copy link** or **Open** to get the public URL. This link:

- Requires **no login** — it's designed to be opened directly on a shared screen/TV browser.
- Refreshes itself automatically at the interval you configured.
- Shows a clear "link inactive" message if you've toggled that wallboard's status to Inactive, so
  turning a public display off is as simple as flipping that toggle — no need to delete anything.

## Related pages

- Live per-agent presence instead of queue-level metrics: [Agent Monitoring](08-agent-monitoring.md).
- Permission requirements: [Permissions & Access](02-permissions.md).
