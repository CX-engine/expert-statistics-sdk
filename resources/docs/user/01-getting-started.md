# Getting Started with Expert Statistics

Expert Statistics turns your phone system's raw call data into dashboards, reports, live wallboards
and AI-generated insights, so you can see how your queues, users and numbers are actually performing
— without exporting spreadsheets or asking IT for a report.

## What you need before you start

1. **A PBX host connected to your account.** Expert Statistics reads data from one PBX host at a
   time. If your organisation has more than one host, you pick which one is "active" from
   **Configuration → PBX Settings → Active Host** (see [PBX Settings](10-pbx-settings.md)).
2. **Expert Statistics activated for that host.** Activation (free trial or paid subscription) is
   something an administrator turns on per host — see
   [Permissions & Access](02-permissions.md#activation-a-separate-switch). If you open any report
   page and see *"This host doesn't have Expert Statistics activated yet"*, that's what's missing —
   ask an administrator to activate it.
3. **Permission to view or edit Expert Statistics**, granted by your administrator. See
   [Permissions & Access](02-permissions.md) for exactly what each level lets you do.

## Where everything lives

Once you're set up, open **Expert stats** in the main sidebar. Every Expert Statistics page shares
the same left-hand sub-navigation:

| Section | What it's for |
|---|---|
| **Home** | Landing page for the module. |
| **Dashboard** | The big-picture view: totals, trends, insight cards, quick actions. See [Dashboard](03-dashboard.md). |
| **My Numbers** | Reporting on your DID (incoming phone) numbers. See [My Numbers & Caller Numbers](06-my-numbers-caller-numbers.md). |
| **Caller Numbers** | Reporting on the numbers *calling you*. Same doc as above. |
| **My Queues** (Dashboard / Report / KPI / Origins) | Everything about call queues. See [My Queues](04-my-queues.md). |
| **My Users** (Dashboard / Report / KPI / Origins) | Everything about individual extensions/agents. See [My Users](05-my-users.md). |
| **Agents** (Realtime Status / Queue Connection / Status Breakdown) | Live and historical agent presence. See [Agent Monitoring](08-agent-monitoring.md). |
| **Call Analyser** | Drill into a single call's full journey (ringing, hold, transfer, voicemail...). See [Call Analysis](07-call-analysis.md). |
| **AI Insights** (Chat / Dashboard / Alerts) | Ask questions in plain English and get AI-generated insights and alerts. See [AI Insights](09-ai-insights.md). |

Outside that sub-navigation, in the main app sidebar under **Configuration**, you'll also find:

- **PBX Settings** — active host, agent status labels, queue pre-answer timing, report-table
  thresholds, AI alert thresholds, resource groups, and wallboards. See [PBX Settings](10-pbx-settings.md).
- **PBX Activation** — administrators only; this is how Expert Statistics gets turned on for a host
  in the first place (see the separate reseller/admin guide if you're an administrator).
- **Scheduled Reports** — manage recurring report emails you've already set up. See
  [Scheduled Reports & Sharing](11-scheduled-reports-and-sharing.md).

## A quick first visit

1. Open **Expert stats → Dashboard**. This gives you the fastest overview: total calls, answer
   rate, lost calls, and 12-month trends.
2. Pick a date range from the period selector (Today, Yesterday, This week, This month, etc.).
3. If a number looks off, click through to **My Queues → Report** or **My Users → Report** to see
   the detail behind it, or open **Call Analyser** to look at individual calls.
4. Use the **Send report** / **Schedule report** buttons on the Dashboard if you want that view
   emailed to yourself or a colleague, once or on a recurring basis.

## Where to go next

- New to the permission model? Start with [Permissions & Access](02-permissions.md).
- Want to understand a specific number (Answer Rate, SLA, Lost Calls...)? See the
  [KPI & Report Glossary](13-kpi-and-report-glossary.md).
- Something not behaving as expected? Check [FAQ & Troubleshooting](14-faq-troubleshooting.md).
