# AI Audit

Shows how your 3CX configuration affects your calls, and exactly what to change in 3CX to improve
your statistics. The audit is computed every day from the 3CX backup of your PBX and crossed with
the calls of the last 30 days (or the period you choose).

> The AI Audit is part of the AI features: it needs them to be activated for your PBX.

## Configuration score

The score estimates the **share of your calls handled without a configuration problem**: 100 %
when the audit has nothing to recommend. Each recommendation lowers it according to:

| Factor | Meaning |
|---|---|
| Impact | A lost call (no answer, no message) weighs more than a degraded experience (long wait, deep menu), which weighs more than a fragile set-up (single agent, agent in too many queues). Upkeep items (fragmented hours, unused apps) cost a few fixed points. |
| Confidence | "To check" items may be intended: they count half. |
| Calls affected | The share of your calls that go through the object concerned. A problem that only exists out of hours, during breaks or on holidays only counts for those calls. |

The score is also given **per area**: routing, queues & agents, office hours, Call Flow Apps.

**Analysis coverage** is the share of your calls whose routing could be fully analysed. IVR menu
keys are not included in 3CX backups, and part of a Call Flow App's logic can be computed while
the call runs: those parts are listed under **Not analysed** and do not lower the score.

Without call statistics, the score is based on the configuration only (labelled as such).

## Recommendations

Sorted by the points each one costs on the score. Each recommendation shows:

- the category: **Probable error**, **To check** (confirm it is not intended before changing
  anything) or **Optimisation**;
- what was found, and **what to change** with the 3CX admin screen and setting;
- the measured statistics and the evidence (the configuration element or figure that proves it).

Filter by category, area, or search a queue, an agent or a DID.

## Routing

Every DID with its first destination, its calls and its number of recommendations. Open a DID to
see its full routing: office hours, out of office and holiday branches, menus and their keys,
queues with their agents, overflows, forwarding, voicemail and end of call. Transfers to one of
your own DIDs and department changes are highlighted.

## Queues and agents

- **Queues**: strategy, agents (logged in at the time of the backup), ring time, maximum wait,
  wrap-up, destination if no answer, hours, with the calls, answered and abandoned rates and the
  average wait of the period.
- **Agents**: every queue agent with their queues, the queue calls offered and answered, and the
  recommendations that concern them (agents answering few calls, in too many queues, never logged
  in, disabled…).

## History, report and refresh

- **History** lists the previous audits and their score.
- **Report** downloads the full audit (routing of every DID and all recommendations) as a
  Markdown file.
- **Re-run the audit** analyses the latest backup again and recomputes the score.

## Related pages

- AI chat, which can explain the audit and combine it with your statistics: [AI Insights](09-ai-insights.md).
- The figures used by the audit: [KPI & Report Glossary](13-kpi-and-report-glossary.md).
