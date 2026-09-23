# AI Documentation Assistant — status & next steps

## What's built (as of this note)

The "Ask AI" tab inside the Documentation panel (`Livewire\Docs\DocsHelperPanel`, mode toggle
Browse/Ask) is a working, read-only Q&A assistant:

- `Livewire\Docs\DocsAssistantChat` — the chat UI, nested inside the Documentation panel rather
  than a second floating button. No persistence: `$messages` lives only in the component's own
  in-memory state for the current page session (see that class's docblock for the three hard
  boundaries it was built with).
- `Contracts\AnswersDocsQuestions` — the responder contract, letting a host app swap the
  implementation without touching the Livewire component.
- `Services\PrismDocsAssistantResponder` — the default implementation. Retrieves the most relevant
  doc sections via `Support\DocsSearch` (plain lexical term-overlap scoring, no embeddings/vector
  store), feeds only that excerpt text to an LLM via Prism (`config('expert-statistics-api.docs_assistant')`
  — provider/model, disabled by default), and asks for a grounded plain-text answer plus an
  optional `suggested_section_id` chosen only from the real catalog.
- Enforced architecturally, not just by convention (see `tests/Architecture/DocsAssistantArchTest.php`):
  neither class references `ExpertStatisticsService`, `DB`, `Cache`, or `Session` at all — it is not
  possible for this assistant to answer a data question or persist a conversation, regardless of
  what the LLM is asked to do.

This deliberately answers a different category of question than the module's existing **AI Chat**
(`src/Livewire/Ai/AiChat.php`) / **AI Floating Chat** (`src/Livewire/Ai/AiFloatingChat.php`), which
answer questions **about the user's call data** ("how many calls did we lose last week?"), grounded
in the backend's `ai/chat` endpoint. This assistant answers **"how do I use Expert Statistics?" /
"where do I find X?"** — navigation and how-to help, grounded only in `resources/docs/user/*.md`.
`AiFloatingChat` is unrelated and still dormant (not referenced in the host app) — its FAB/expand
visual structure was a useful reference when designing the chat bubbles, nothing more; don't wire
this assistant through it or its route (`expert-stats.ai.chat`).

## Explicitly not built (next step, when asked for)

Everything above is read-only: the assistant can only ever *talk about* creating a report, creating
a resource group, or changing the active host — it has no way to actually do any of those things.
Making it able to requires a real, separate design pass before any code goes here, not a quiet
capability creep into `PrismDocsAssistantResponder`:

- An explicit **tool-calling** layer (Prism supports `withTools()`) - e.g. a `create_resource_group`
  tool wrapping `ExpertStatisticsService`'s existing group-management calls, a
  `set_active_host` tool wrapping `ResolvesActivePbxHost::setActiveHost()`, a
  `schedule_report` tool wrapping the same path `ShareReportModal` uses.
- Every such tool **must** check `expert-statistics.modify` (the same permission
  `ChecksExpertStatisticsModifyPermission` already gates manual edits behind) before acting - a
  view-only user asking the assistant to "create a group for me" must be refused exactly like the
  manual UI refuses them, not routed around that check via the chat.
- This is a materially different trust boundary than today's read-only assistant (which cannot
  mutate anything no matter what it's asked), so it deserves its own confirmation step in the UI
  (e.g. "I'll create a group named X with these members - confirm?") rather than acting silently on
  a single message.
- No decision has been made on which actions to expose first, or the confirmation UX - that's a
  product call for whoever picks this up.
