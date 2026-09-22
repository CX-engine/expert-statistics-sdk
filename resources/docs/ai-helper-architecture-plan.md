# Plan: AI Documentation Assistant (floating chat) — not yet built

This is a groundwork note, not an implementation. Nothing described here exists in code yet — it's
recorded so a future session can build it without re-deriving the design from scratch.

## What this is (and isn't)

A future floating chat button (FAB), separate from the module's existing **AI Chat**
(`src/Livewire/Ai/AiChat.php`) and **AI Floating Chat** (`src/Livewire/Ai/AiFloatingChat.php`)
components. Those answer questions **about the user's call data** ("how many calls did we lose last
week?"), grounded in the backend's `ai/chat` endpoint for the active PBX host.

This new assistant answers a different category of question entirely: **"how do I use Expert
Statistics?" / "where do I find X?"** — navigation and how-to help, grounded in the documentation
written for this module (`resources/docs/user/*.md`, rendered in-app via the
`DocsHelperPanel` component — see that component's own doc comment for the render pipeline). It
should never call the stats API and never see call data. Keep the two chat features visually and
architecturally distinct so users don't confuse "ask about my calls" with "ask how this page works."

## Why not just extend `AiFloatingChat`

`AiFloatingChat` already exists in this package but — per a code search done while building the
Documentation Helper panel — is not referenced anywhere in the host app (`bluerocktel-cx`); it's
dormant. Its FAB/expand/tabs/message-bubble structure is a reasonable **visual** reference to copy
from, but its purpose (data Q&A) and its backend call (`ExpertStatisticsService::sendAiChatMessage()`)
are wrong for this feature — don't wire this new assistant through it or its route
(`expert-stats.ai.chat`).

## Proposed shape

- **Component**: `src/Livewire/Docs/DocsAssistantChat.php` (new, separate from `Ai/*`), rendering a
  FAB in the opposite screen corner from `AiFloatingChat`'s `fixed bottom-6 right-6` (e.g.
  `bottom-6 left-6`) so the two can coexist without overlapping once `AiFloatingChat` is eventually
  wired up too.
- **Responder contract**: a small interface, e.g. `Contracts\AnswersDocsQuestions`, with one method
  along the lines of `answer(string $question, ?string $currentRouteName): DocsAssistantAnswer`
  (a DTO with `answer: string`, `sources: array<sectionId>`, `suggestedRouteName: ?string`). This
  indirection lets the first implementation be simple keyword/section matching against the
  `DocsCatalog` (see below), with a real LLM-backed implementation swapped in later without
  touching the Livewire component.
- **First (non-AI) implementation**: score each doc section in `DocsCatalog::sections()` by keyword
  overlap with the question (a cheap RAG-lite: no embeddings, just term matching against section
  titles + a short excerpt), return the best-matching section's content plus its route (if the
  section maps to a page — see `DocsCatalog`'s route-context mapping, already built for the help
  panel's auto-open behaviour) as `suggestedRouteName`. This alone would satisfy "answer questions
  and redirect to the right page" without any external AI call.
- **Real AI implementation** (later): given this codebase already has a Mistral-backed AI stack on
  the backend (`expert-stats`'s `MistralKeyPoolService`, `AiChatController`) and this app already
  uses Prism elsewhere per `bluerocktel-cx`'s own conventions, either route would work — feed the
  matched doc section(s) as context alongside the user's question (retrieval-augmented, not
  fine-tuning) rather than sending the entire doc set on every request.
- **Config**: a new top-level key in `config/expert-statistics-api.php`, e.g.
  `'docs_assistant' => ['enabled' => env('EXPERT_STATISTICS_DOCS_ASSISTANT_ENABLED', false), ...]`,
  matching the existing `env()`-backed, commented-block convention in that file. Ship it disabled by
  default until an implementation actually backs it.
- **Translations**: a new `docs_assistant.*` group in `resources/lang/{en,fr}/pbx.php`, matching the
  existing `ai_widget_*` naming pattern already used for `AiFloatingChat`'s strings.

## Explicitly deferred

- No Livewire component, route, or service class for this exists yet — do not half-build the FAB
  without a working responder behind it (an empty/non-functional floating button is worse than no
  button).
- No decision has been made on LLM provider/cost for the "real AI implementation" step — that's a
  product/budget call for whoever picks this up, not an engineering default to assume.
