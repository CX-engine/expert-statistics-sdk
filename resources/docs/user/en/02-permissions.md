# Permissions & Access

Expert Statistics uses two independent layers of access control. Understanding the difference
saves a lot of confusion about "why can I see this but not change it?".

## Layer 1 — View vs. Modify (this is the one that affects you day to day)

Your administrator grants you one of these permissions:

| Permission | What it lets you do |
|---|---|
| **`expert-statistics.view`** (read-only) | Browse **every** Expert Statistics page — Dashboard, My Queues, My Users, My Numbers, Caller Numbers, Agent Monitoring, Call Analyser, AI Insights, Scheduled Reports, and every tab of PBX Settings. You can filter, change date ranges, switch hosts, and read everything. |
| **`expert-statistics.modify`** (read + write) | Everything `.view` gives you, **plus** the ability to actually save changes: PBX Settings tabs (agent labels, queue pre-answer times, report-table thresholds, AI alert thresholds), creating/editing/deleting resource **Groups**, building/editing **Wallboards**, and editing/deleting **Scheduled Reports**. |

A shorthand permission, `expert-statistics.*`, grants both at once.

**The important part: view-only is not "can't see configuration."** If you only have
`expert-statistics.view`, you can still open PBX Settings and look at every tab — you'll see a
"read-only" banner, and every Save/Create/Delete button is disabled or hidden. You need
`.modify` to actually change something.

This split applies to:

- Every tab in **PBX Settings** (Active Host, Agent, Queue, Report Table, AI Alerts, Groups,
  Wallboard) — viewable by anyone with `.view`, editable only with `.modify`.
- **Scheduled Reports** — the list is viewable by anyone with `.view`; editing or deleting an
  existing schedule requires `.modify`.
- **Sending or scheduling a new report** from the Dashboard/report pages (the Share/Schedule
  modal) also requires `.modify`.

If a button looks greyed out or missing and you believe you should be able to use it, ask your
administrator to grant you `expert-statistics.modify`.

## Layer 2 — Activation (a separate switch)

Even with full `expert-statistics.modify` permission, none of the report pages will work until an
**administrator activates Expert Statistics for your PBX host** — either as a free trial or a paid
subscription. This is a completely separate mechanism from the view/modify permissions above; it's
managed on the **PBX Activation** page, which only administrators can access.

If a host isn't activated, every report page (except the Dashboard's underlying access check, which
uses its own equivalent gate) shows a message along these lines:

> *This host doesn't have Expert Statistics activated yet. Improve customer satisfaction and team
> productivity by analysing your calls by queue, user or number... Ask an administrator to activate
> Expert Statistics for this host, or get in touch to enable it.*

If you see this, there's nothing to configure on your end — contact your administrator. (If you
*are* the administrator, see the separate Reseller/Admin guide for how to activate a host.)

## Layer 3 — PBX Host management (administrators only)

Creating, editing or deleting the PBX hosts themselves (connection details, credentials, licence
info) is a third, entirely separate admin-only capability, unrelated to `expert-statistics.*`. It's
covered in the Reseller/Admin guide, not here — as a regular user you'll never need it.

## Quick reference

| Question | Answer |
|---|---|
| "I can see a page but every Save button is disabled." | You have `.view` but not `.modify`. Ask an admin. |
| "I get a 402 / 'not activated yet' message." | The host isn't activated. Ask an admin to activate it. |
| "I don't see Expert Stats in the sidebar at all." | You don't have `expert-statistics.view` (or `.modify`/`.*`). Ask an admin. |
| "I can't create or edit PBX hosts." | That's intentional — host management is an administrator-only capability, separate from Expert Statistics permissions. |
