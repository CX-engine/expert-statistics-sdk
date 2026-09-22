# Call Analyser (Analyse Call Details)

While the other report pages aggregate many calls, the **Call Analyser** lets you drill into a
**single call's full journey** from start to finish — exactly what happened, in order.

## Finding a call

Use the filters (date range, queue, extension, caller/DID number, **Exclude closed hours**) to
narrow down a list of calls, then open one to see its detail.

## Reading the call flow

Each call is broken into labelled segments, shown in sequence with their duration:

| Segment | Meaning |
|---|---|
| **Answered** | Time actually talking to someone. |
| **Ringing** | Time the call spent ringing before being picked up (or not). |
| **Missed** | The call rang out without being answered. |
| **Transferred** | The call was handed off to another extension/queue. |
| **On Hold** | The caller was placed on hold. |
| **Voicemail** | The call went to voicemail. |
| **IVR** | Time spent in an automated menu ("press 1 for...") before reaching a person. |
| **In Queue** | Time spent waiting in a queue for an available agent. |
| (Transit) | A brief in-transit/routing segment between other stages. |

Durations are shown in a compact "Xm Ys" format so you can quickly see, for example, that a call
spent 45 seconds in the IVR, 2 minutes in queue, then 4 minutes 12 seconds talking.

## Why this is useful

The Call Analyser is the right tool when a report number looks wrong and you want to know *why* —
for example, a queue's average wait time looks high, and the Call Analyser lets you find the
specific calls responsible and see exactly where the time went (IVR menu too long? Held too long
after answering? Bounced between queues before landing?).

## Exporting

You can export the underlying CDR (call detail record) data for the filtered set of calls.

## Related pages

- For aggregated versions of this same data, see [My Queues](04-my-queues.md) or
  [My Users](05-my-users.md).
- For the meaning of "closed hours" filtering, see
  [KPI & Report Glossary](13-kpi-and-report-glossary.md).
