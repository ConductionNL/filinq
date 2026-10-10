---
kind: code
---

# Proposal: guided-document-wizard

## Why

Every generation path Filinq ships today assumes the user already knows the
full request shape: `POST /api/documents/generate` takes a `templateId`, raw
`dataRefs` (`{register, schema, id}` triples) and an `adHocData` blob (verified
at HEAD: `DocumentController::parseGenerateParams()`,
`DataResolverService::resolve()`). That is a developer/power-user contract. The
market's answer to "how does a clerk create a correct document?" is a **guided
interview**:

- **SmartDocuments** (150+ gemeenten) sells "Q&A wizard document creation —
  end users build a document step-by-step by answering questions" as its
  killer feature (spectr `competitor_features`, competitor theme #6).
- **docassemble** (the OSS reference) is a "guided-interview expert system —
  question order computed from what the document needs; multilingual;
  accessible" (spectr).
- Demand is concrete: GH
  [#96](https://github.com/ConductionNL/filinq/issues/96) (open, verified)
  asks for Procest-driven document generation and cites **475 tender sources**
  mentioning document generation — every one of those flows ends with a human
  answering case-specific questions before the letter goes out.

Without a wizard, Filinq's template estate (Twig today, office templates
after Wave-1 `office-template-authoring`) is only usable from API integrations
or by staff who understand registers and schemas. The wizard closes the gap
between "template exists" and "clerk produces a correct beschikking",
priority **should-have**.

## What Changes

- **Wizard definitions as register objects.** A new `wizardDefinition` schema
  in the `templates` register: an ordered list of questions (types `text`,
  `choice`, `date`, `registerObject`), each with an optional skip-logic
  condition on an earlier answer, attached to exactly one template via
  `templateId`. No change to the `template` schema itself.
- **Wizard CRUD + lookup API** (`api/wizards/…`, `GET
  api/templates/{id}/wizard`) so the template detail page can author the
  interview and the generate flow can discover it.
- **Clerk-facing wizard runner UI** that walks the questions in order,
  evaluates skip-logic client-side, renders a register-object picker for
  `registerObject` questions, shows a review step, and generates the document
  through the **existing** `POST /api/documents/generate` endpoint — no
  parallel generation path.
- **Deterministic answer translation**: `registerObject` answers become
  `dataRefs` entries (resolved server-side by `DataResolverService`, nested
  resolution included); `text`/`choice`/`date` answers become `adHocData`
  values at their `mapsTo` path (ad-hoc precedence per DCS-005 unchanged).
- **Server-side answer validation + reproducibility**: when a generate request
  carries `options.wizardContext`, `DocumentService` validates the answers
  against the wizard definition (required questions, visible-by-condition,
  choice membership) and records the wizard id, wizard object version, and the
  full answer set on the `generatedDocument` object — a generation becomes
  replayable evidence.
- **"Generate from register object" entry point**: pick an object (register,
  schema, UUID) → the wizard prefills every answer it can derive from that
  object's resolved data and only asks the remaining questions.
- **Template-type parity**: the wizard works identically for `twig` and
  `office` templates (Wave-1 `office-template-authoring` REQ-DDOTA-003 branches
  on `templateType` inside `generateDocument()`; the wizard sits in front of
  that branch and needs no knowledge of it).

GDPR note (config rule): the wizard introduces one new personal-data location —
wizard answers persisted on `generatedDocument` for reproducibility. This is
the same data that already flows through `adHocData`/`dataRefs` into the
document itself; storage is purpose-bound (audit/regeneration, DCS-072
extension), local-only, and documented in design.md Security.

## Capabilities

### New Capabilities

- `guided-document-wizard`: guided-interview document generation — wizard
  definitions (ordered questions, skip logic, register-object pickers)
  attached to templates, a clerk-facing runner UI, deterministic answer
  translation into the existing generate contract, server-side validation,
  and object-driven prefill.

### Modified Capabilities

- `document-creatie-sjablonen`: the generation audit trail (DCS-072 family) is
  extended — wizard-driven generations record the interview context
  (wizard id + version + answers) on the `generatedDocument` object, and
  `options.wizardContext` is validated server-side before rendering.

## Impact

- **Backend**: new `WizardService` (CRUD, answer validation, prefill via
  `DataResolverService`), new `WizardController` + routes; small extension in
  `DocumentService::generateDocument()` (validate + log `wizardContext`).
- **Register**: `lib/Settings/filinq_register.json` — new `wizardDefinition`
  schema in the `templates` register (version bump, additive on top of
  Wave-1's `2.1.0`); `generatedDocument` schema gains optional
  `wizardContext` (document register bump `2.2.0` → `2.3.0`, additive; `2.2.0`
  is the verified current version at HEAD). Apply order is pinned: this change
  applies **first** and takes the document register to `2.3.0`; the
  co-scheduled `multi-format-output` change applies **after** it and bumps
  `2.3.0` → `2.4.0` — no rebase-on-whichever-lands-second.
- **Frontend**: wizard authoring panel on `TemplateDetail.vue`, new wizard
  runner view + register-object picker using `@conduction/nextcloud-vue`
  components (ADR-012); "Generate with wizard" entry points on the template
  index and from object context.
- **Dependencies**: none new. ADR-011 check: OpenRegister owns object
  reads (used via `ObjectService`/`DataResolverService`); no OR
  `lib/Formats/`/`lib/Service/` utility implements interview/skip-logic
  semantics — this is Filinq domain logic.
- **Sibling boundaries**: no OpenRegister/OpenConnector/Procest changes; GH #96
  event-driven auto-generation stays a separate change (the wizard is the
  human-in-the-loop counterpart).

## Out of Scope

- Computed question ordering ("ask only what the document needs", docassemble
  style) — this wave ships explicit author-defined order + conditions.
- Multi-user / resumable wizard sessions persisted server-side (a run is one
  clerk, one sitting; abandoning the browser discards the draft).
- Citizen/external-facing wizards (portaliq territory, ADR-046).
- Event-driven auto-generation from Procest lifecycle events (GH #96 proper).
- Office/Twig template authoring changes — the wizard consumes templates as-is.

## Success Criteria

- `openspec validate guided-document-wizard --strict` exits 0.
- A clerk can attach a wizard to an existing template, run it, and download
  the generated document without ever seeing a register slug or UUID.
- A `registerObject` answer is resolved server-side via `DataResolverService`
  with the same nested-resolution behaviour as a hand-written `dataRefs` entry.
- A generate request with `options.wizardContext` fails 422 when a required,
  visible question is unanswered; the produced `generatedDocument` object
  carries wizard id, wizard version, and answers.
- The same wizard generates correctly against a `twig` template and an
  `office` template (given both exist for the same schema binding).
- `composer check:strict` and the unit suite pass with zero new violations.
