# Scheduled Reports & Sharing

Any report or Dashboard view can be **sent once by email** or **scheduled to repeat** — both start
from the same **Share/Schedule** modal, opened via the "Send report" / "Schedule report" buttons
you'll find on the Dashboard and the report pages.

> Requires `expert-statistics.modify` — a view-only user cannot send or schedule reports. See
> [Permissions & Access](02-permissions.md).

## What actually happens when you "share" a report

There's no shareable link and no attached file to download from the modal itself — sharing a report
creates a server-side delivery record that emails the chosen recipients the report content directly
(the Wallboard feature is different — see [Wallboard & Live Data](12-wallboard-live-data.md) for
its shareable public link).

## Sending once vs. scheduling

The modal has two modes, depending on which button you clicked:

- **Send report** — delivers the current view by email once, right away.
- **Schedule report** — delivers it repeatedly, on the frequency you choose.

Fields in both modes:

- **Nickname** — an optional name for the report, to recognise it later.
- **Recipients** — add one or more email addresses (your own address is pre-filled to start).
- **Frequency** (schedule mode only) — Day, Week, or Month.
- **Resource group** (report pages only, not the Dashboard) — optionally scope the report to a
  [Groups tab](10-pbx-settings.md#groups-tab) group instead of the individually-selected elements.

Submitting shows a confirmation notification; the date range and other filters you had active on
the page when you opened the modal come along automatically.

## Managing existing schedules

The **Scheduled Reports** page (Configuration section of the sidebar) lists every recurring report
you've created — one-off "sent now" reports don't appear here, only ones set to repeat. The table
shows Name, Type, the elements/DIDs it covers, its Resource Group (if any), Recipients, Start date,
and Frequency.

- **Edit** (pencil icon) opens a panel where you can rename the report, add/remove recipient email
  addresses, change its resource group (which also updates the covered elements), and adjust which
  queues/extensions/DIDs/callers it includes.
- **Delete** (trash icon) removes the schedule after a confirmation prompt.

You cannot change a schedule's frequency or convert it between "send once" and "repeat" from this
page — those are set when the report is first created via the Share/Schedule modal; to change them,
delete the old schedule and create a new one with the settings you want.

## Related pages

- Where the "Resource group" option comes from: [PBX Settings → Groups tab](10-pbx-settings.md#groups-tab).
- Read/edit permission split: [Permissions & Access](02-permissions.md).
