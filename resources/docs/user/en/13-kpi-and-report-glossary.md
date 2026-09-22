# KPI & Report Glossary

A single reference for exactly how each number is calculated. If a figure on a page doesn't match
your own manual count, check its formula here first — most apparent mismatches come down to a
detail in how "answered", "abandoned" or "lost" are defined.

## Core inbound KPIs (Dashboard, My Queues, My Users)

| KPI | Formula |
|---|---|
| **Total calls** | Sum of all inbound calls in the period. |
| **Answered** | Sum of calls actually picked up. |
| **Lost calls** | Total calls − Abandoned − Answered (never below 0). "Lost" specifically means the caller hung up while genuinely waiting for an agent — not during the pre-answer/IVR stage. |
| **Average wait time** | Total waiting duration ÷ total answered calls. |
| **Short calls** | Calls answered within 10 seconds — flagged separately because a high count can mean either fast service or accidental pickups/hang-ups worth reviewing. |
| **Answer Rate %** | Answered ÷ (Total calls − pre-answer-abandoned calls) × 100. The pre-answer-abandoned exclusion means Answer Rate reflects how well you served calls that were genuinely waiting for an agent, not diluted by callers who hung up during an IVR menu before reaching a queue. |
| **Best / Worst hour** | The hour with the highest / lowest Answer Rate % in the period. |
| **Busiest hour** | The hour with the highest raw call volume (not necessarily the best- or worst-served hour). |

## Outbound KPIs

Same shape as inbound, but simpler — there's no "abandoned" concept for calls you place:

| KPI | Formula |
|---|---|
| **Lost calls (outbound)** | Total outbound calls − Answered (never below 0). |
| **Answer Rate % (outbound)** | Answered ÷ Total outbound calls × 100 (no pre-answer exclusion). |

## Trend KPIs (Dashboard → 12-Month Trends)

| KPI | Formula |
|---|---|
| **Call Answer Rate (CAR)** | Answered inbound calls ÷ Inbound calls × 100, compared month over month. |
| **Average wait time (trend)** | The API's reported average waiting duration for the month. |
| **Trend %** | (Current month − Previous month) ÷ Previous month × 100. Shown as 0% if there's no data for the previous month to compare against. |

## Agent Monitoring KPI

| KPI | Meaning |
|---|---|
| **Online** | Agent is registered on the PBX and available. |
| **Away** | Agent is registered but marked away. |
| **Unavailable** | Everything else, including not registered at all. |

## Report table columns (My Queues / My Users Report)

| Column | Meaning |
|---|---|
| **Calls** | Total calls reaching this row (queue, or queue+extension). |
| **Unanswered — Declined** | Calls not answered by an agent. |
| **Unanswered — Abandoned** | Calls the caller hung up before being answered (pre-answer-abandoned count shown in parentheses when non-zero). |
| **Internal / Solicited** | Internal calls into a queue, or calls specifically routed to an extension. |
| **Answered** | Calls picked up. |
| **Transferred** | Calls handed off elsewhere after being answered. |
| **Rate %** | See Answer Rate % above — colour-coded green ≥80%, yellow ≥60%, red below (thresholds are configurable, see [PBX Settings → Report Table tab](10-pbx-settings.md#report-table-tab)). |
| **Talk dur. / Wait dur.** | Total talk time / total wait time for the row. |

## "Consolidation" — two different meanings, don't confuse them

1. **"Consolidate" row toggle** (My Queues Report, queues-only view) — merges every selected
   queue's numbers into a single combined row, purely for display. It doesn't change how any call
   is counted, just how many rows you see.
2. **"Multi-queue call consolidation" toggle** — counts a caller's call **once**, even if it passed
   through several queues before being answered, instead of once per queue it touched. This affects
   the actual call counts, not just row grouping.

Both toggles exist independently on the same page and can be combined. Neither one merges data
**across different PBX hosts** — every report is always scoped to a single active host at a time
(switch hosts via [PBX Settings → Active Host](10-pbx-settings.md#active-host-tab)).

## "Exclude closed hours"

A single toggle, present on nearly every report/export page, that filters out calls recorded during
your PBX's configured closed/off hours — so a queue or user that's only staffed part of the day
isn't penalised by after-hours volume they were never meant to answer.

## Call Analyser segment labels

See [Call Analysis](07-call-analysis.md#reading-the-call-flow) for the full list (Answered,
Ringing, Missed, Transferred, On Hold, Voicemail, IVR, In Queue).
