# accessible-redaction-output Specification (delta)

---
status: proposed
---

## Purpose

Tag-preserving accessible redaction as a DocuDesk leaf over OpenRegister's
redaction engine. Dutch anonymisation software routinely strips a PDF's tag
structure, breaking screen-reader accessibility — a hard WCAG procurement gate
and, since the European Accessibility Act deadline (2025-06-28), a live legal
defect (R3 section C, 5 gov sources incl. the RVIHH quote; top-ranked unspecced
demand, R3 E #1). Because redaction is OpenRegister's engine (ADR-022; verified
at HEAD via `AnonymizationService` → `DocumentProcessingHandler`), the
tag-preservation fix lives in OR's parallel `tag-preserving-redaction` change,
which makes the processing result carry a `structurePreservation` block. This
change is the DocuDesk half: request preservation (default on for PDF), consume
and surface the outcome, record it on `anonymizationLink`, gate publication
clearance on it (default warn), and — when the active `verapdf-validation`
change is present — upgrade a self-reported outcome to a validator-backed fact.
It is distinct from `pdfua-accessible-output` (generated templates); this change
owns redaction output only.

## ADDED Requirements

### Requirement: Request tag-structure preservation on redaction jobs (REQ-DDARO-001)

DocuDesk MUST request tag-structure preservation from OpenRegister's
document-processing engine on every PDF anonymisation/redaction run, including
folder and batch runs, by passing a `preserveTags` option that defaults to ON
for PDF inputs. The request MUST be additive to the existing anonymise call and
MUST NOT introduce a DocuDesk-local PDF tag-rewriting engine — tag preservation
is owned by OpenRegister's `tag-preserving-redaction` change. Formats that
cannot carry PDF tags MUST pass the option through and rely on the engine's
reported loss reason rather than failing.

#### Scenario: PDF redaction requests preservation by default

- GIVEN a tagged source PDF submitted for anonymisation
- WHEN DocuDesk invokes OpenRegister's redaction engine
- THEN the call carries `preserveTags: true` by default
- @e2e tests/e2e/spec-coverage/accessible-redaction-output.spec.ts

#### Scenario: The default can be disabled by an administrator

- GIVEN `docudesk.redaction.preserve_tags_default` is set to false
- WHEN a PDF is redacted
- THEN `preserveTags` is passed as false and the outcome is recorded accordingly
- @e2e exclude admin-config default is backend logic — covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)

### Requirement: Surface the structure-preservation outcome (REQ-DDARO-002)

DocuDesk MUST read the `structurePreservation` block
(`{requested, preserved, tagCountBefore, tagCountAfter, lossReasons[]}`) from
OpenRegister's processing result and surface it in the document report and the
anonymisation review UI as an accessibility state — `preserved`, `degraded`,
or `not-applicable` — with the tag counts and human-readable loss reasons, and
a prominent flag when degraded. When the block or a field is absent (an
OpenRegister without the engine change), DocuDesk MUST treat the outcome as
`unknown` and surface it as unverified — it MUST NOT report a false `preserved`
and MUST NOT crash.

#### Scenario: Preserved redaction shows an accessibility-preserved state

- GIVEN a redaction whose processing result reports `preserved: true` with equal before/after tag counts
- WHEN the operator opens the document report
- THEN an "accessibility preserved" state is shown with the tag counts
- @e2e tests/e2e/spec-coverage/accessible-redaction-output.spec.ts

#### Scenario: Degraded redaction shows a prominent flag with loss reasons

- GIVEN a redaction whose result reports `requested: true, preserved: false` with a `lossReasons` entry
- WHEN the report renders
- THEN a prominent "accessibility degraded" flag is shown with the loss reasons
- @e2e tests/e2e/spec-coverage/accessible-redaction-output.spec.ts

#### Scenario: Absent block is reported as unknown, never false-preserved

- GIVEN a processing result with no `structurePreservation` block
- WHEN DocuDesk maps the outcome
- THEN the state is `unknown` (unverified), never `preserved`, and no error is raised
- @e2e exclude fail-safe mapping is backend logic — covered by PHPUnit (tests/unit/Service/RedactionAccessibilityServiceTest.php::testAbsentBlockIsUnknown)

### Requirement: Gate publication clearance on preserved accessibility (REQ-DDARO-003)

When a redacted document's structure was lost, DocuDesk MUST gate publication/
clearance according to `docudesk.redaction.accessibility_gate`: `warn` (default)
proceeds but attaches a prominent, recorded flag to the clearance decision;
`block` prevents clearance until an operator overrides with a recorded reason;
`off` records the outcome without gating. The gate MUST default to `warn` so it
never hardens an existing publication flow by surprise, and MUST sit alongside
the existing prohibition/consent clearance checks, not replace them. An
`unknown` outcome MUST be treated as degraded for the gate.

#### Scenario: Degraded redaction warns and flags at clearance by default

- GIVEN the gate is `warn` (default) and a degraded redaction is cleared for publication
- WHEN clearance is evaluated
- THEN clearance proceeds AND a prominent accessibility-degraded flag is attached to and recorded on the clearance decision
- @e2e tests/e2e/spec-coverage/accessible-redaction-output.spec.ts

#### Scenario: Block mode stops clearance until a reasoned override

- GIVEN the gate is `block` and a degraded redaction is submitted for clearance
- WHEN clearance is evaluated
- THEN clearance is blocked until an operator overrides with a recorded reason
- @e2e exclude gate-mode branching is backend logic — covered by PHPUnit (tests/unit/Service/RedactionAccessibilityServiceTest.php)

### Requirement: Record the outcome on the anonymizationLink object (REQ-DDARO-004)

DocuDesk MUST record the structure-preservation outcome on the run's
`anonymizationLink` object as a `structurePreservation` sub-object (requested,
preserved, tagCountBefore, tagCountAfter, lossReasons, and veraPdfVerified when
applicable), alongside the existing replacement counts. The recorded outcome
MUST contain no entity values and no document content (AVG Art. 5(1)(c) data
minimisation) — only tag counts and loss reasons. The `anonymizationLink`
schema MUST gain this sub-object with a register version bump.

#### Scenario: The redaction run records its accessibility outcome

- GIVEN a completed PDF redaction with a preserved outcome
- WHEN the `anonymizationLink` object for that run is inspected
- THEN it carries a `structurePreservation` sub-object with the tag counts and no entity values
- @e2e exclude object-shape assertion is backend logic — covered by PHPUnit (tests/unit/Service/RedactionAccessibilityServiceTest.php)

### Requirement: Optional veraPDF-backed verification when verapdf-validation is present (REQ-DDARO-005)

When the `verapdf-validation` capability is present, DocuDesk MUST ask it to
verify that the redacted output is genuinely tagged/valid and record the result
as `veraPdfVerified`; a validator contradiction (output not actually valid
despite a self-reported `preserved`) MUST downgrade the state to `degraded`.
DocuDesk MUST NOT integrate veraPDF directly — the integration is owned by
`verapdf-validation` — and when that capability is absent DocuDesk MUST fall
back to the engine's self-reported outcome, labelled as engine-reported and not
validator-verified, without error.

#### Scenario: veraPDF confirms a preserved redaction

- GIVEN `verapdf-validation` is present and a redaction self-reports `preserved: true`
- WHEN DocuDesk runs the verification hook
- THEN veraPDF confirmation is recorded as `veraPdfVerified: true`
- @e2e exclude requires the verapdf binary/capability — covered by PHPUnit with a fake verapdf-validation capability (tests/unit/Service/RedactionAccessibilityServiceTest.php)

#### Scenario: veraPDF contradiction downgrades a self-reported outcome

- GIVEN `verapdf-validation` is present and a redaction self-reports `preserved: true` but the output is not actually valid
- WHEN the verification hook runs
- THEN the state is downgraded to `degraded` and flagged
- @e2e exclude validator-contradiction path is backend logic — covered by PHPUnit (tests/unit/Service/RedactionAccessibilityServiceTest.php::testVeraPdfContradictionDowngrades)

#### Scenario: Absent verapdf-validation leaves the engine outcome standing

- GIVEN `verapdf-validation` is not present
- WHEN DocuDesk maps a redaction outcome
- THEN the engine's self-reported outcome stands, labelled engine-reported-only, with no error
- @e2e exclude presence-gate fallback is backend logic — covered by PHPUnit (tests/unit/Service/RedactionAccessibilityServiceTest.php)
