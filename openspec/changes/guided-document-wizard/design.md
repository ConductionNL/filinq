# Design: guided-document-wizard

## Context

Verified current state (HEAD of this worktree):

- `POST /api/documents/generate` → `DocumentController::generate()` parses
  `templateId`, `dataRefs`, `options`, `filename`
  (`parseGenerateParams()`); `options` is passed through to
  `DocumentService::generateDocument()` untouched, so a new
  `options.wizardContext` key rides the existing contract without a route or
  controller-signature change.
- `DataResolverService::resolve(array $dataRefs, array $adHocData)` resolves
  `{register, schema, id}` triples via OR `ObjectService`, keys results by
  schema, merges `adHocData` on top (ad-hoc wins — DCS-005), returns
  `{data, errors, warnings}` with per-ref errors, and supports nested
  resolution to 3 levels (DCS-003).
- `DocumentService::generateDocument()` renders (Twig sandbox today; branches
  on `templateType` after Wave-1 `office-template-authoring` REQ-DDOTA-003),
  produces `pdf|odf|html`, and logs a `generatedDocument` object (register
  `document` v2.2.0) with `templateId`, `templateVersion`, `dataRefs`,
  `format`, `warnings` (DCS-051/072).
- Templates live in the `templates` register (v2.0.0 at HEAD; v2.1.0 after
  Wave-1) with schemas `template`, `templateVersion` (+ Wave-1 `textFragment`,
  `templateImportJob`). Wave-1 adds optional `boundRegister`/`boundSchema` on
  `template` — the wizard's `registerObject` questions and prefill lean on
  that binding when present.
- Frontend: `src/store/store.js` configures the shared
  `createObjectStore('filinq-objects')` (nc-vue `useObjectStore`) against
  the canonical OR base — the register-object picker reads through it, no new
  OR client code.

Constraint (openspec/config.yaml): all processing local, data via
OpenRegister only (ADR-001), `@conduction/nextcloud-vue` components (ADR-012),
Controller → Service → Mapper layering (ADR-008), NL Design tokens (ADR-003).

## Goals / Non-Goals

**Goals:**

- A clerk generates a correct document by answering questions — never by
  assembling `dataRefs` or knowing schemas.
- Wizard authoring is a form on the template detail page, owned by the same
  people who own templates.
- Every wizard-driven generation is reproducible: definition version + answers
  are on the audit object.
- One generation path: the wizard is a *front-end* to
  `POST /api/documents/generate`, not a sibling of it.
- Works unchanged for `twig` and `office` templates.

**Non-Goals:**

- No computed question ordering (docassemble's solver) — explicit order +
  conditions only.
- No server-persisted draft sessions (lesson GH #287: no in-memory session
  stores either — a run lives in the browser until submission).
- No external/citizen-facing wizard (portaliq, ADR-046).
- No changes to template rendering, the Twig sandbox, or the office fill path.

## Decisions

### D1 — Wizard = one register object attached by `templateId` (no `template` schema edit)

New `wizardDefinition` schema in the `templates` register:

- `name`, `description`, `namespace` (same ownership model as templates,
  REQ-TMPL-03 semantics).
- `templateId` (string, UUID of the template it fronts, required).
- `active` (boolean, default true) — at most one *active* wizard per template;
  `WizardService` enforces this on save (deactivates nothing implicitly,
  refuses a second active wizard with 409).
- `questions` (array, ordered) — see D2.

The attachment direction is wizard → template so the `template` schema (being
extended by Wave-1 in the same release train) is not touched twice; lookup is
`GET /api/templates/{id}/wizard` (a filtered OR query on
`templateId + active`). **Relationship note (canonical-spec touch
discipline):** this adds a schema to the templates register but does not
modify the `template`/`templateVersion` data model, so no requirement-level
`template-management` delta is needed. Per decision C3 (single source of
truth) the new `wizardDefinition` schema **is** registered in the canonical
`openspec/specs/template-management/spec.md` header — its "Templates Register
Schemas" listing and `**OpenSpec changes**` list — while the full
requirements (question model, validation, translation, prefill, runner) remain
in this change's own capability spec (REQ-DDGDW-*). The templates-register
version bump is additive on top of Wave-1's `2.1.0`.

### D2 — Question model: four types, dotted `mapsTo`, one-level conditions

Each entry in `questions`:

| Field | Meaning |
|---|---|
| `key` | Stable identifier, unique within the wizard (slug-like) |
| `label`, `helpText` | Clerk-facing texts (English source keys, NL translations — ADR-005) |
| `type` | `text` \| `choice` \| `date` \| `registerObject` |
| `required` | boolean |
| `choices` | for `choice`: array of `{value, label}` |
| `register`, `schema` | for `registerObject`: the OR register/schema slugs to pick from |
| `mapsTo` | dotted data path the answer lands on in the template context (e.g. `aanvrager.naam`); empty for `registerObject` (the whole object lands under its schema key, exactly like a hand-written dataRef) |
| `condition` | optional `{questionKey, operator, value}` with `operator` ∈ `equals` \| `notEquals` \| `answered` — references an **earlier** question only |

Rejected: arbitrary boolean expression trees (unauditable, over-general for
v1) and Twig expressions as conditions (would leak template-sandbox semantics
into form logic). Forward references are rejected at save time so evaluation
is a single forward pass. **Fail-safe rule:** a malformed or dangling
condition makes the question *visible*, never silently skipped — under-asking
is the unsafe failure mode for a legal document.

### D3 — Answer translation is deterministic and reuses the existing contract

At submission the runner (client) and `WizardService::translateAnswers()`
(server, single source of truth — the client calls a dry-run endpoint or
ships the same mapping, but the server-side translation is authoritative)
produce:

- every answered `registerObject` question → one `dataRefs` entry
  `{register, schema, id}` (so `DataResolverService` does the fetch, nested
  resolution, and error shaping — the wizard never fetches object data for
  generation itself);
- every answered `text`/`choice`/`date` question → `adHocData` at its
  `mapsTo` path (dot-notation expansion; `adbario/php-dot-notation` is already
  a dependency);
- the untranslated raw answers → `options.wizardContext = {wizardId,
  wizardVersion, answers}`.

Precedence stays exactly DCS-005: ad-hoc (wizard scalar answers) over
resolved object data. This is deliberate — a clerk's explicit answer beats a
stale object field, and it is the documented existing semantics rather than a
new rule.

### D4 — Validation + audit hook inside `generateDocument()` (no parallel endpoint)

When `options.wizardContext` is present, `DocumentService` (via injected
`WizardService::validateAnswers()`) validates **server-side** before
rendering: wizard exists and is active for this `templateId`; every required
question that is *visible* under the condition semantics of D2 has an answer;
`choice` answers are members of `choices`; `date` answers parse ISO 8601;
`registerObject` answers correspond to a submitted dataRef. Failure → 422
with per-question errors (same error-shaping style as
`DataResolverService`'s per-ref errors). Success → after generation the
`generatedDocument` object gains `wizardContext` (wizard id, the wizard
object's OR version at run time, answers). Requests **without**
`wizardContext` are untouched — the API contract for existing callers does
not change.

Rejected: a separate `POST /api/wizards/{id}/generate` endpoint — it would
duplicate auth, format handling, response shaping and bulk semantics of the
existing endpoint (redundant-controller gate; ADR-022 spirit) and split the
audit trail. The assignment explicitly keeps generation on
`api/documents/generate`.

### D5 — Prefill endpoint reuses `DataResolverService`

`POST /api/wizards/{id}/prefill` with `{register, schema, id}` resolves the
object through `DataResolverService` (same nested resolution, same error
shape) and returns `{answers: {questionKey: suggestedValue}, unresolved:
[questionKey…]}`:

- a `registerObject` question whose `register`/`schema` match the entry
  object → prefilled with the object's id;
- a scalar question whose `mapsTo` path resolves in the resolved data →
  prefilled with that value (displayed as editable suggestion, never
  auto-submitted);
- everything else → `unresolved`, the wizard asks it.

This is the "generate from register object" entry point: object context menu /
index action opens the runner with the prefill applied. Prefill is
server-side so picker questions and scalar suggestions share one resolution
path and RBAC ride-along (the OR read happens as the requesting user).

### D6 — Runner UI (ADR-012)

New `src/views/wizard/WizardRunner.vue` (opened from template index "Generate
with wizard", template detail, or an object entry point): one question per
step, progress indicator, back navigation preserving answers, skip-logic
evaluated on every answer change, `registerObject` steps render a picker
backed by the shared `useObjectStore` (search + paginate, REQ-TMPL-06-style
querying on the OR API), review step showing every visible Q/A, then submit →
`POST /api/documents/generate` → existing download handling. Authoring UI is
a panel on `TemplateDetail.vue` (question list with reorder, per-question
form, condition builder limited to earlier questions). Dialogs live in
`src/modals/` (modal-isolation); all components `@conduction/nextcloud-vue`
(`CnFormDialog`, `CnDataTable`, `NcSelect` with `inputLabel`); NL Design
tokens only (ADR-003); fully keyboard-operable (WCAG AA — an interview UI
that needs a mouse fails its own purpose).

### D7 — Template-type parity is structural, not implemented twice

The wizard produces `dataRefs + adHocData + options`; `generateDocument()`
already branches on `templateType` (Wave-1 REQ-DDOTA-003) *after* data
resolution. Therefore parity is a tested guarantee, not new code: the same
wizard attached to a Twig template and an office template (same
`boundSchema`) yields the same resolved context. The only wizard-visible
difference is the format list offered at the review step (odf/docx
availability — surfaced by the co-scheduled `multi-format-output` change when
present; the runner reads whatever the generate API advertises and degrades
to `pdf` otherwise).

### Declarative vs imperative (ADR-031)

The wizard definition, questions, and conditions are **pure data** in the
register (declarative, audit-readable). Imperative code is confined to:
condition evaluation + answer validation/translation (domain logic no
`x-openregister-*` annotation can express) and the existing generation
pipeline. No lifecycle/aggregation/notification annotations are added; no OR
behaviour extensions are used or needed.

## OpenRegister usage (ADR-001)

| Operation | OR service |
|---|---|
| Wizard CRUD + template lookup | `ObjectService` (`saveObject`/`find`/`searchObjectsPaginated`/`deleteObject`) via `OpenRegisterResolver`, same pattern as `TemplateService` |
| Prefill + generation data | existing `DataResolverService` → OR `ObjectService` (no new fetch path) |
| Register-object picker | frontend shared `useObjectStore` against the canonical OR API |
| Generation audit | existing `generatedDocument` object, extended with `wizardContext` |

No custom database tables. Register import stays
`ConfigurationService::importFromApp()` on boot; templates register bump is
additive on top of Wave-1 (`2.1.0` → `2.2.0`), document register `2.2.0` →
`2.3.0` (`2.2.0` verified current at HEAD). **Apply order (pinned):** this
change applies first (document register → `2.3.0`); the co-scheduled
`multi-format-output` change applies after it (document register `2.3.0` →
`2.4.0`, adding `docx` + `outputs`). The two bumps touch disjoint additive
properties of `generatedDocument`; register import is idempotent. No
rebase-on-whichever-lands-second.

## Seed Data

Municipality-flavoured demo objects (nil-UUID pattern, Demostad flavour,
aligned with Wave-1's seed template
`00000000-0000-0000-0000-000000000101` "Beschikking parkeervergunning"):

```json
{
  "wizardDefinition": {
    "id": "00000000-0000-0000-0000-000000000201",
    "name": "Beschikking parkeervergunning — begeleide aanmaak",
    "namespace": "filinq",
    "templateId": "00000000-0000-0000-0000-000000000101",
    "active": true,
    "questions": [
      {"key": "dossier", "label": "Which dossier is this decision for?", "type": "registerObject", "required": true, "register": "dossier", "schema": "dossier"},
      {"key": "besluit", "label": "What is the decision?", "type": "choice", "required": true, "choices": [{"value": "toegewezen", "label": "Granted"}, {"value": "afgewezen", "label": "Rejected"}], "mapsTo": "besluit.uitkomst"},
      {"key": "afwijzingsreden", "label": "Reason for rejection", "type": "text", "required": true, "mapsTo": "besluit.afwijzingsreden", "condition": {"questionKey": "besluit", "operator": "equals", "value": "afgewezen"}},
      {"key": "ingangsdatum", "label": "Effective date", "type": "date", "required": true, "mapsTo": "besluit.ingangsdatum", "condition": {"questionKey": "besluit", "operator": "equals", "value": "toegewezen"}}
    ]
  }
}
```

Unit-test fixtures reuse this shape with synthetic nil-UUID subjects; the
seeded dossier register already ships Demostad demo dossiers
(`demostad-woo-2025-017` family, verified) for a live wizard run on the dev
instance.

## Security Considerations

- **No new data-access path**: object reads happen via `DataResolverService`
  / the OR API as the authenticated user; the wizard never widens what a
  clerk could already fetch (the picker shows only what OR RBAC returns).
- **Server-side validation is authoritative** (ADR-005 spirit): client-side
  skip logic is UX; the server re-evaluates visibility + requiredness and
  rejects invalid submissions with 422. Answers are data, never templates —
  they enter rendering as context values (Twig autoescape / PhpWord
  `setValue()` XML-escaping), so no injection into template logic.
- **GDPR (config rule)**: `wizardContext.answers` on `generatedDocument` is
  personal data (it feeds a document about a person). Purpose: audit +
  reproducibility (extends DCS-072). It is stored in OR (RBAC'd, audited),
  never leaves the instance, and is deleted with the `generatedDocument`
  object. Documented in `docs/features/` privacy note.
- **Routes**: every new controller method carries explicit auth attributes
  (`#[NoAdminRequired]` + authenticated-user guard, matching
  `DocumentController`); wizard authoring respects the template lock
  (REQ-TMPL-11) — a wizard save against a template locked by another user is
  refused 423, since the wizard changes what the template produces.
- **Fail-closed conditions**: malformed condition → question shown + required
  enforced (D2), never a silent skip of a legally required answer.

## Risks / Trade-offs

- [Wizard drifts from template tags/schema] → wizard save validates `mapsTo`
  paths and `registerObject` register/schema against the template's
  `boundRegister`/`boundSchema` (when set) and warns on unknown paths — same
  warn-don't-block philosophy as Wave-1 tag validation; the review step +
  generation warnings (DCS-014) are the runtime net.
- [Answer precedence surprises: clerk answer overrides object field] →
  deliberate (D3) and shown on the review step ("overrides resolved value").
- [One active wizard per template is limiting for variant flows] → variants
  are separate templates (duplicate + wizard travels via prefill of the
  authoring form); revisit if real estates demand N:1.
- [Prefill exposes object data in suggestions] → prefill runs as the
  requesting user through the same OR reads the picker already does; no
  elevation.
- [Browser-local runs lose work on crash] → accepted for v1 (Non-Goal);
  answers are typically minutes of work, and server drafts would create a new
  PII store.
