# AI Assistant — status & next steps

## What's built (as of this note)

The "Ask AI" floating chat bubble (bottom-right, next to the Documentation panel's own bottom-left
bubble) can do two things now:

1. **Read-only Q&A** (always available): explains how to use Expert Statistics, grounded only in
   `resources/docs/user/*.md`.
2. **Actions** (only when `config('expert-statistics-api.ai_actions.enabled')` is true — a separate
   flag from the Q&A bubble's own `docs_assistant.enabled`, off by default): can PROPOSE
   creating/scheduling a `Pbx3cxReport` or creating a `Pbx3cxHostResourceGroup` on the user's behalf.
   It never executes either itself — only ever after an explicit human Confirm click.

### Read-only Q&A layer (unchanged from before this note's previous revision)

- `Livewire\Docs\DocsAssistantChat` — the chat UI. No persistence: `$messages`/`$pendingAction` live
  only in the component's own in-memory state for the page session.
- `Contracts\AnswersDocsQuestions` => `Services\PrismDocsAssistantResponder` — retrieves relevant doc
  sections via `Support\DocsSearch` (lexical scoring, no embeddings), asks an LLM for a grounded
  plain-text answer + optional `suggested_section_id`. Enforced architecturally (`tests/Architecture/DocsAssistantArchTest.php`):
  this ONE class must never reference `ExpertStatisticsService` — it is the one component
  guaranteed incapable of answering a data question, regardless of `ai_actions.enabled`.

### Action layer (new)

- **Backend** (`expert-stats`): `app/Http/Controllers/AiHelper/{AiHelperReportController,AiHelperResourceGroupController}.php`,
  routes `v1.2/{pbx}/ai-helper/{reports,resource-groups}[/validate]`. Each resource has a `/validate`
  endpoint (dry-run, never persists, full field validation the legacy manual-UI controllers lack) and
  a real endpoint (re-validates, checks `Pbx3cxHost::isAiActivated()`, then creates). This is the
  **existing** `ai_activated` boolean on the backend's own `Pbx3cxHost` model (unrelated to
  bluerocktel-cx's Expert Statistics trial/subscription activation) — bluerocktel-cx still has no way
  to set it (console command only, unchanged, deferred per explicit product decision when this was
  scoped).
- **SDK** (`expert-stats-api-sdk-php`): `Resources\AiHelperResource` (`->validateReport()`,
  `->createReport()`, `->validateResourceGroup()`, `->createResourceGroup()`), wired onto
  `ExpertStatisticsConnector::aiHelper()`.
- **Package**: `ExpertStatisticsService::{validateAiHelperReport,createAiHelperReport,validateAiHelperResourceGroup,createAiHelperResourceGroup,askCallAnalytics}()`
  (the last one is a thin wrapper around the pre-existing `sendAiChatMessage()` — this is how the
  action assistant "talks to the AI analyser" for judgment calls like "my busiest queues").
  `Exceptions\AiFeaturesNotActivatedException` — thrown on a 403 from a create call, caught
  specifically by the Livewire layer.
- `Contracts\PerformsExpertStatisticsActions` => `Services\PrismActionAssistantResponder` — two Prism
  calls per turn: a `Prism::text()->withTools([ask_call_analytics])->withMaxSteps()` "gather" stage
  (read-only, the model may freely look up real data when a request needs a judgment call), then a
  `Prism::structured()` "finalize" stage (given the gather stage's findings, doc excerpts, the current
  in-flight `pendingAction` for revision, and the active host's available queues/extensions for
  name→id grounding) that produces the reply plus an optional `pending_report`/`pending_resource_group`
  proposal. The model never executes anything — it only ever proposes.
- `DocsAssistantChat::confirmAction()` is the **only** path that mutates anything in this whole
  feature, and only ever reachable from an explicit UI button click: checks `expert-statistics.modify`
  (via the existing `ChecksExpertStatisticsModifyPermission` trait, responding gracefully in-chat
  rather than aborting), calls `validateAiHelper*` first, and only calls `createAiHelper*` after a
  successful validation — never skips straight to create. A validation failure keeps the proposal on
  screen for correction; a not-activated 403 or an unexpected error each get their own friendly
  message (the latter keeps the proposal so the user can just retry).
- `explainCapabilities()` — the "What can I do?" button — is a canned, translated, non-LLM message
  (like the starter suggestions), not a model call.

This deliberately still answers a different category of question than the module's existing **AI
Chat** (`src/Livewire/Ai/AiChat.php`) / **AI Floating Chat** (`src/Livewire/Ai/AiFloatingChat.php`),
which answer questions **about the user's call data** directly. `AiFloatingChat` remains unrelated
and dormant (not referenced in the host app).

## Explicitly still not built

- Editing or deleting existing reports/resource groups via the assistant.
- Switching the active host via the assistant.
- Any bluerocktel-cx sync/migration/admin UI for the backend's `ai_activated` flag — still
  console-command-only, by explicit product decision.
- Any new dedicated permission for the action layer — it reuses `expert-statistics.modify` as-is,
  the same permission that already gates every other manual mutation in this module.

If any of the above is picked up later, it needs its own scoping pass — none of it was in scope for
the two-skill "create/schedule a report, create a resource group" feature this note now documents.
