# guided-document-wizard Specification (delta)

---
status: proposed
---

## Purpose

Guided-interview document generation: a `wizardDefinition` register object
(ordered questions with skip logic, attached to one template) plus a
clerk-facing runner UI that walks the questions, translates answers into the
existing `POST /api/documents/generate` contract (`dataRefs` + `adHocData`),
validates server-side, and supports an object-driven entry point with prefill.
Works identically for `twig` and `office` templates. SmartDocuments Q&A wizard
/ docassemble model; demand evidenced by GH #96 (475 tender sources).

## ADDED Requirements

### Requirement: Wizard definitions are register objects attached to a template (REQ-DDGDW-001)

The app MUST store wizard definitions as objects of a new `wizardDefinition`
schema in the `templates` register, each attached to exactly one template via
`templateId` and carrying an ordered `questions` array. Each question MUST
declare a unique `key`, a `label`, a `type` from exactly `text`, `choice`,
`date`, `registerObject`, and a `required` flag; `choice` questions MUST
declare `choices` (`{value, label}` pairs), `registerObject` questions MUST
declare OR `register` and `schema` slugs, and scalar questions MAY declare a
dotted `mapsTo` data path. At most one wizard with `active: true` MAY exist
per template; saving a second active wizard MUST be refused with HTTP 409.
Saving a wizard for a template locked by another user (REQ-TMPL-11) MUST be
refused with HTTP 423. The `template` and `templateVersion` schemas MUST NOT
be modified by this change.

#### Scenario: Author saves a valid wizard for a template

- GIVEN an existing template and no active wizard attached to it
- WHEN a `wizardDefinition` with ordered questions of all four types is saved via the wizard API
- THEN the object is persisted in the `templates` register via OpenRegister
- AND `GET /api/templates/{id}/wizard` returns it
- @e2e tests/e2e/spec-coverage/guided-document-wizard.spec.ts

#### Scenario: Second active wizard on the same template is refused

- GIVEN a template with an active wizard
- WHEN a second `wizardDefinition` with `active: true` and the same `templateId` is saved
- THEN the API responds HTTP 409 and the second wizard is not persisted
- @e2e exclude conflict-shape API assertion without UI surface beyond an error toast — covered by PHPUnit (tests/unit/Service/WizardServiceTest.php::testSecondActiveWizardIsRefused)

### Requirement: Wizard definitions are validated at save time (REQ-DDGDW-002)

The `WizardService` MUST validate a wizard definition on every save and refuse
structurally invalid definitions with HTTP 422 and per-question errors:
duplicate question keys, a `choice` question without choices, a
`registerObject` question without register/schema slugs, and a `condition`
referencing a question that does not exist or that appears later in the order
MUST all be refused. When the attached template declares
`boundRegister`/`boundSchema`, a `mapsTo` path or `registerObject`
register/schema that does not match the bound schema MUST produce a
non-blocking warning in the save response (warn, not block — mirroring the
office-template-authoring tag-validation philosophy).

#### Scenario: Forward-referencing condition is refused

- GIVEN a wizard definition where question 2 has a condition on question 5
- WHEN the definition is saved
- THEN the API responds HTTP 422 naming question 2 and the forward reference
- @e2e exclude validation-shape assertion; covered by PHPUnit (tests/unit/Service/WizardServiceTest.php::testForwardConditionRefused)

#### Scenario: Unknown mapsTo path warns but saves

- GIVEN a template bound to a schema without property `foo.bar`
- WHEN a wizard with a question mapping to `foo.bar` is saved
- THEN the wizard is persisted
- AND the save response carries a warning naming the question and the unknown path
- @e2e tests/e2e/spec-coverage/guided-document-wizard.spec.ts

### Requirement: Skip-logic conditions evaluate deterministically and fail safe (REQ-DDGDW-003)

Question visibility MUST be computed in a single forward pass over the
question order: a question without a `condition` is visible; a question with
`condition` `{questionKey, operator, value}` is visible iff the referenced
earlier answer satisfies the operator (`equals`, `notEquals`, or `answered`).
A hidden question's answer MUST be discarded (never translated, never
validated as required). A malformed or non-evaluable condition MUST make the
question visible — the fail-safe direction is asking too much, never silently
skipping a required answer. The same evaluation semantics MUST be applied by
the runner UI (live, as UX) and by the server (authoritative, at validation
time).

#### Scenario: Conditional question appears only on the triggering answer

- GIVEN the seed wizard where "Reason for rejection" is conditional on decision equals "afgewezen"
- WHEN the clerk answers "toegewezen" and then changes the answer to "afgewezen"
- THEN the rejection-reason step is absent from the flow for "toegewezen"
- AND it appears (and is required) after the answer changes to "afgewezen"
- @e2e tests/e2e/spec-coverage/guided-document-wizard.spec.ts

#### Scenario: Malformed condition falls back to visible

- GIVEN a persisted wizard whose condition references an operator unknown to the evaluator
- WHEN visibility is computed server-side
- THEN the question is treated as visible and its `required` flag is enforced
- @e2e exclude corrupted-definition edge not constructable through the authoring UI — covered by PHPUnit (tests/unit/Service/WizardServiceTest.php::testMalformedConditionIsVisible)

### Requirement: Clerk-facing wizard runner generates through the existing endpoint (REQ-DDGDW-004)

The app MUST ship a wizard runner UI that walks the visible questions in
order (one step at a time, with progress, back navigation preserving answers,
and live skip-logic), renders a register-object picker backed by the shared
OpenRegister object store for `registerObject` questions, shows a review step
listing every visible question and answer, and on confirmation generates the
document via the existing `POST /api/documents/generate` endpoint — the
wizard MUST NOT introduce a parallel generation endpoint. The runner MUST be
reachable from the template index/detail (only when an active wizard exists)
and MUST be fully keyboard-operable using `@conduction/nextcloud-vue`
components (ADR-012) and NL Design System tokens (ADR-003).

#### Scenario: Clerk runs the wizard end to end

- GIVEN the seed template with its active wizard and a Demostad dossier object
- WHEN the clerk opens "Generate with wizard", picks the dossier, answers the questions, and confirms on the review step
- THEN the browser issues one `POST /api/documents/generate` with the wizard's translated payload
- AND the generated document downloads with the existing response handling
- @e2e tests/e2e/spec-coverage/guided-document-wizard.spec.ts

#### Scenario: Runner is keyboard-operable

- GIVEN the wizard runner is open
- WHEN the clerk completes a run using only the keyboard
- THEN every question type (including the object picker) and the review confirmation are operable without a pointer
- @e2e tests/e2e/spec-coverage/guided-document-wizard.spec.ts

### Requirement: Answers translate deterministically into the existing generate contract (REQ-DDGDW-005)

Answer translation MUST map every answered visible `registerObject` question
to one `dataRefs` entry `{register, schema, id}` (resolved server-side by
`DataResolverService` with its existing nested resolution and per-ref error
shaping) and every answered visible scalar question to an `adHocData` value
at its `mapsTo` dotted path, with the raw answer set carried as
`options.wizardContext = {wizardId, wizardVersion, answers}`. Precedence MUST
remain the existing DCS-005 rule (ad-hoc over resolved data). When
`options.wizardContext` is present, the server MUST validate the answers
against the active wizard before rendering — missing required visible
answers, non-member `choice` values, unparseable `date` values, or a
`registerObject` answer without its corresponding dataRef MUST fail the
request with HTTP 422 and per-question errors. Requests without
`wizardContext` MUST behave exactly as before this change.

#### Scenario: Register-object answer resolves like a hand-written dataRef

- GIVEN a wizard run where the dossier question was answered with dossier UUID "…017"
- WHEN the generate request is processed
- THEN `dataRefs` contains `{register: "dossier", schema: "dossier", id: "…017"}` and the template context carries the resolved dossier fields under the schema key
- @e2e exclude payload-shape equivalence assertion; covered by PHPUnit (tests/unit/Service/WizardServiceTest.php::testTranslateAnswersProducesDataRefs)

#### Scenario: Missing required visible answer fails with 422

- GIVEN a generate request whose `wizardContext` omits the answer to a required visible question
- WHEN `POST /api/documents/generate` is called
- THEN the response is HTTP 422 naming the unanswered question key
- AND no document is generated and no `generatedDocument` object is written
- @e2e tests/e2e/spec-coverage/guided-document-wizard.spec.ts

#### Scenario: Clerk answer overrides the resolved object value

- GIVEN a scalar answer whose `mapsTo` path collides with a resolved object field
- WHEN the data context is built
- THEN the clerk's answer wins (DCS-005 ad-hoc precedence)
- AND the review step had marked the answer as overriding a resolved value
- @e2e exclude precedence pin; covered by PHPUnit (tests/unit/Service/WizardServiceTest.php::testAdHocPrecedencePinned)

### Requirement: Object-driven entry point prefills the wizard (REQ-DDGDW-006)

The app MUST provide a "generate from register object" entry point: given an
entry object (`register`, `schema`, `id`), `POST /api/wizards/{id}/prefill`
MUST resolve the object via `DataResolverService` (as the requesting user)
and return `{answers, unresolved}` where a `registerObject` question matching
the entry object's register/schema is prefilled with its id, a scalar
question whose `mapsTo` path resolves in the object data is prefilled with
that value, and every other question is listed `unresolved`. The runner MUST
apply prefilled values as editable suggestions — a prefilled answer MUST
remain reviewable and changeable before submission, and prefill MUST NOT
auto-submit any step.

#### Scenario: Wizard started from a dossier asks only the remaining questions

- GIVEN the seed wizard and a Demostad dossier whose data resolves the dossier question
- WHEN the wizard is started from that dossier's entry point
- THEN the dossier question is prefilled with the dossier's id and shown as an editable suggestion
- AND the decision question (unresolvable from the object) is listed unresolved and asked
- @e2e tests/e2e/spec-coverage/guided-document-wizard.spec.ts

### Requirement: The wizard works identically for Twig and office templates (REQ-DDGDW-007)

The wizard MUST be attachable to templates of both `templateType` values
(`twig` and `office`, per office-template-authoring REQ-DDOTA-006) and MUST
produce the same translated payload (`dataRefs`, `adHocData`,
`wizardContext`) regardless of template type — the type branch happens inside
`DocumentService::generateDocument()` (REQ-DDOTA-003), after the wizard's
work is done. The runner MUST NOT contain template-type-specific question
logic; only the offered output formats MAY differ by template type as
advertised by the generation API.

#### Scenario: Same wizard payload for both template types

- GIVEN a Twig template and an office template bound to the same schema, each with an identical wizard
- WHEN the same answers are submitted through both wizards
- THEN both generate requests carry identical `dataRefs`, `adHocData`, and `wizardContext.answers`
- AND both generations succeed through their respective render paths
- @e2e tests/e2e/spec-coverage/guided-document-wizard.spec.ts
