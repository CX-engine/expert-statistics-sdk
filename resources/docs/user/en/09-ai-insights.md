# AI Insights

Three AI-powered pages, all grounded in your own PBX host's actual call/queue/agent data — not
generic chatbot knowledge.

> Note: this "AI Insights" module (Chat / Dashboard / Alerts) answers **questions about your call
> data** ("how many calls did we lose last week?"). It's a different feature from the **Ask AI**
> tab in this same Documentation panel (next to Browse), which answers questions about **how to
> use Expert Statistics itself** and can't see your call data at all.

## AI Chat

A conversational assistant for your stats. Type a question in plain English and get an answer
grounded in your host's data — replies can include plain text, KPI cards, or full tables, plus
follow-up suggestion chips you can click to keep digging without retyping.

- **Starter suggestions** appear when you open a new conversation, if you're not sure what to ask.
- **History** — every conversation is saved; the left sidebar lists past conversations by title,
  time, and message count, and you can reopen any of them.
- **New conversation** — the "+" button starts a fresh thread.
- **Clear history** — removes all saved conversations (confirmation required).
- Other pages (like AI Dashboard's "quick action" cards) can deep-link straight into a pre-filled
  question here.

## AI Dashboard

A grid of AI-generated insight panels, refreshed periodically or on demand:

| Panel type | What it shows |
|---|---|
| Alert summary | A rollup of current alert conditions. |
| Trend / KPI snapshot | A metric with its up/down percentage change. |
| Pattern | Recognised patterns, e.g. recurring peak-hour behaviour. |
| Recommendation | Numbered, actionable suggestions with the reasoning behind them. |
| Suggestion | Per-agent or per-queue observations. |
| Quick action | A clickable shortcut that jumps you into AI Chat with a relevant question pre-filled. |

Each panel is colour-coded by severity (critical / warning / info). Unread panels show a pulsing
indicator; click a panel to mark it read, or use **Mark all read**. Each panel can be individually
dismissed. A **Refresh** button forces the AI to regenerate insights immediately instead of waiting
for the next scheduled refresh; if you've never generated insights yet, use the **Generate
insights** button on the empty state.

## AI Alerts

Alerts the system has already raised, grouped into: **Queue performance**, **Peak hours**,
**Volume**, and **Agent performance**, with a count per category and summary cards for total /
critical / warning / info counts. Expand an alert to see its description, a progress bar for
rate-based metrics, and a recommendation. Use **Check now** to force an immediate re-check instead
of waiting for the next scheduled one, or **Dismiss** to clear an individual alert.

### Configuring what triggers an alert

Alert *thresholds* aren't set here — they live in
**PBX Settings → AI Alerts tab** (see [PBX Settings](10-pbx-settings.md#ai-alerts-tab)), where you
control whether alerting is enabled at all, how often it checks, which language/email it notifies,
and the specific warning/critical thresholds for abandon rate, not-answered rate, pre-answer
abandonment, wait time, and volume-change sensitivity. Changing thresholds requires
`expert-statistics.modify` — see [Permissions & Access](02-permissions.md).

## Related pages

- Threshold configuration: [PBX Settings → AI Alerts tab](10-pbx-settings.md#ai-alerts-tab).
- The underlying numbers behind an AI answer: [KPI & Report Glossary](13-kpi-and-report-glossary.md).
