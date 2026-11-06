# AI Audit

Shows how your 3CX configuration affects your calls, and exactly what to change in 3CX to improve
your statistics. The audit is computed every day from the 3CX configuration of your PBX and crossed with
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
keys are not included in the 3CX configuration, and part of a Call Flow App's logic can be computed while
the call runs: those parts are listed under **Not analysed** and do not lower the score.

Without call statistics, the score is based on the configuration only (labelled as such).

## Recommendations

Sorted by the points each one costs on the score. Each recommendation shows:

- the category: **Probable error**, **To check** (confirm it is not intended before changing
  anything) or **Optimisation**;
- what was found, and **what to change** with the 3CX admin screen and setting;
- the measured statistics and the evidence (the configuration element or figure that proves it).

Filter by category, area, or search a queue, an agent or a DID. The queue, agent or DID of a
recommendation is a link: open it, then use **Back to …** above the tab to return where you were.

### This is expected

Some behaviour is intended for your PBX: a closing message after the wait, a menu that hangs up
when nobody chooses, agents shared between campaign queues. Click **This is expected** on the
recommendation (with a note if you want): it no longer counts in the score and is no longer
reported, in this audit and the next ones. **Marked as expected** lists them, and **Report again**
brings one back. Changing them requires the right to modify the Expert Statistics settings.

## Routing

Every DID with its first destination, its calls and its number of recommendations. Open a DID to
see its call flow sheet, read left to right then top to bottom:

- top row, the only arrows: the inbound rule, the first object (menu, queue or Call Flow App with
  its special cases and the web services it calls) and the decision it makes (office hours, or the
  states a Call Flow App routes on, with the hours written in its script);
- one column per state or menu key, with the steps written as text and the sub-decisions indented;
- the 3CX destinations reached (queues with strategy, agents, maximum wait and overflow; menus;
  voicemail; external numbers) grouped in their own row, with no line to the columns so lines never
  cross, and in red the fallback used when the Call Flow App's web services fail;
- the points to watch on this DID. **⚠** marks an object with recommendations: hover it, or a point
  to watch, to read them, click it to go to the recommendation below.

When a Call Flow App chooses destinations during the call, the calls actually observed (where
they went, how many) are shown under the recommendations.

## Queues and agents

- **Queues**: strategy, agents (logged in when the configuration was read), ring time, maximum wait,
  wrap-up, destination if no answer, hours, with the calls, answered and abandoned rates and the
  average wait of the period.
- **Agents**: every queue agent with their queues, the queue calls presented and answered, the
  hours logged in to each queue over the period, and the recommendations that concern them (agents
  answering few calls, never logged in, disabled, shared with a queue that loses calls…). The
  status (profile, logged in or out) is the one **when the configuration was read**, not over the period.

## History, report and refresh

- **History** lists the previous audits and their score.
- **Report** downloads the full audit (routing of every DID and all recommendations) as a
  Markdown file.
- **Re-run the audit** analyses the latest 3CX configuration again and recomputes the score.

## Files of the audit

**Configuration → PBX Settings → AI Audit files** ([PBX Settings](10-pbx-settings.md)) shows what the audit is computed from, and lets an administrator
provide:

- the **backup configuration** (.xml file of the 3CX backup), when the backups of the PBX are not
  collected: it is checked, then the audit is run again on it;
- the **Call Flow sources** of each Call Flow App (Call Flow Designer project, .zip), to replace
  whenever the Call Flow changes;
- the **audio messages** (.zip of the .wav files of the backup): they are transcribed, so the
  audit knows what callers hear (a closing message, a menu, an invitation to call back).

Every file is checked before it is kept; a refused file shows why.

## Related pages

- AI chat, which can explain the audit and combine it with your statistics: [AI Insights](09-ai-insights.md).
- The figures used by the audit: [KPI & Report Glossary](13-kpi-and-report-glossary.md).
