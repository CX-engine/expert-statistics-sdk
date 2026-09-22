# FAQ & Troubleshooting

**"I see a message saying this host doesn't have Expert Statistics activated yet."**
Activation is separate from your view/edit permissions — it's a per-host switch (trial or paid
subscription) that only an administrator can turn on. Ask your administrator to activate it from
the PBX Activation page. See [Permissions & Access](02-permissions.md#layer-2--activation-a-separate-switch).

**"A Save/Create/Delete button is greyed out or missing."**
You have `expert-statistics.view` (read-only) but not `expert-statistics.modify`. Everything is
still visible to you, but changing it requires the edit permission. Ask your administrator to grant
it if you need it. See [Permissions & Access](02-permissions.md).

**"I don't see Expert Stats in the sidebar at all."**
You don't currently have any Expert Statistics permission. Ask your administrator to grant
`expert-statistics.view` at minimum.

**"A number in the Report table doesn't match what I counted manually."**
Check the exact formula in the [KPI & Report Glossary](13-kpi-and-report-glossary.md) — the most
common surprise is Answer Rate % excluding pre-answer-abandoned calls from its denominator, and
"Lost calls" specifically excluding abandoned calls (they're counted separately, not lumped in).

**"What's the difference between 'Consolidate' and 'Multi-queue call consolidation'?"**
They're two different toggles on the My Queues Report page — one merges table *rows*, the other
de-duplicates a single caller's call across queues. Neither merges data across different PBX hosts.
Full explanation: [KPI & Report Glossary](13-kpi-and-report-glossary.md#consolidation--two-different-meanings-dont-confuse-them).

**"Can I combine data from two of our PBX hosts into one report?"**
No — every report always queries a single "active" host at a time. Switch which one via
[PBX Settings → Active Host](10-pbx-settings.md#active-host-tab). If you regularly need to compare
hosts, run the same report on each and compare manually, or ask your administrator whether a
second host should be set up as your active one for a session.

**"Where did my scheduled report go / how do I change its frequency?"**
Manage existing schedules from the **Scheduled Reports** page. You can edit recipients, its
resource group, and which elements it covers — but not its frequency or send-once-vs-repeat
setting. To change those, delete the schedule and create a new one via the Share/Schedule modal.
See [Scheduled Reports & Sharing](11-scheduled-reports-and-sharing.md).

**"My wallboard link shows 'this link is inactive'."**
Its Active/Inactive toggle on the Wallboard tab has been switched off. Toggle it back on to restore
the public link without needing to recreate it. See [Wallboard & Live Data](12-wallboard-live-data.md).

**"AI Chat gave me an answer that doesn't match the Report page."**
AI Chat answers are grounded in the same underlying data as the reports, but always for your
currently active host and whatever time frame it inferred from your question — if a number looks
off, ask it to clarify the exact date range and host it used, or cross-check against the equivalent
Report page directly.

**"I can't create or edit PBX hosts, or access PBX Activation."**
That's expected — those are administrator-only capabilities, entirely separate from Expert
Statistics view/modify permissions. See [Permissions & Access](02-permissions.md#layer-3--pbx-host-management-administrators-only).
