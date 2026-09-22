# My Numbers & Caller Numbers

Two related but different reports:

- **My Numbers** — reports on *your* DID (Direct Inward Dialing) numbers: the phone numbers people
  dial to reach you.
- **Caller Numbers** — reports on the numbers *calling you*, i.e. your callers' phone numbers.

Both are single-page reports (no Dashboard/KPI/Origins sub-pages) with the same shape: a date
range, a searchable/filterable table, an **Exclude closed hours** toggle, and an Excel export.

## My Numbers (DID report)

One row per DID number, showing call volume and answer performance for that specific inbound
number — useful when you have several published numbers (e.g. per department, per campaign, per
region) and want to know which ones are actually being used and how well they're served.

## Caller Numbers

One row per calling number (or grouped by caller, depending on your filters), showing how often
that number has called, whether calls were answered, and average call duration. This is the view
to use when you want a "who keeps calling us and are we picking up" report — for example to spot a
VIP customer who's struggling to get through, or a number calling repeatedly in a short window.

Third-party number history/lookup data (list, history, last-seen) also feeds this report where
available, so recurring callers are easier to recognise across visits.

## Related pages

- To group several DIDs or caller numbers together for reporting purposes, see
  [PBX Settings → Groups](10-pbx-settings.md#groups-tab).
- To schedule either report by email, see
  [Scheduled Reports & Sharing](11-scheduled-reports-and-sharing.md).
