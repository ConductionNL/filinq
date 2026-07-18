# document-validation-checks Specification (delta)

---
status: proposed
---

## Purpose

Extend the document validation check catalogue with validator-backed PDF/UA
accessibility checks (veraPDF `ua1` / Matterhorn Protocol), riding the
existing `accessibility` category (wave-1 REQ-DDPUA-003) and the existing
profile/severity/verdict mechanism, with an honest unavailable-validator
finding instead of a silent skip. This delta only ADDs requirements; the
profiles-defaults requirement is NOT modified here (`verapdf-validation`
already amends it to default validator-backed checks to `off`, and that rule
governs these checks too).

## ADDED Requirements

### Requirement: The check catalogue MUST include validator-backed PDF/UA accessibility checks (REQ-DDPUM-005)

`DocumentValidationService` MUST implement validator-backed accessibility
checks grouped under the existing finding `category: "accessibility"`
(alongside the wave-1 heuristic checks, which keep their ids), riding the
existing catalogue/profile/severity mechanism:

- `pdfua-conformance-failed` — veraPDF reports the PDF non-compliant with
  PDF/UA-1 (`ua1` flavour); finding params carry the flavour, the
  failed-checkpoint count and the top Matterhorn clause/test references
  (`pdfua-verapdf-matterhorn` REQ-DDPUM-001).
- `accessibility-validator-unavailable` — a validator-backed accessibility
  check is enabled in the resolved profile but the veraPDF binary is absent or
  disabled; an explicit "not validated" finding (severity `warning`,
  non-escalatable) so absence is visible, never a silent skip.

These checks MUST run only for PDF files and only when the resolved profile
enables them (per-profile opt-in — validator invocations cost a JVM start per
document), MUST default to `off` in every shipped profile (validator-backed
checks are an explicit admin opt-in, per the profiles rule
`verapdf-validation` already establishes), and MUST carry checkpoint and
clause references only — never document content. Aggregation
(`validationStatus`) and the 422 blocking gate apply to these findings exactly
as to existing checks. The wave-1 heuristic accessibility checks
(`pdf-not-tagged`, `pdf-language-missing`, `pdf-title-missing`,
`pdfua-identifier-missing`) MUST be left unchanged — they remain the
always-available floor.

#### Scenario: Non-conformant PDF fires the validator-backed accessibility finding

- GIVEN a profile with `pdfua-conformance-failed` at severity `warning`, an available validator, and a PDF that passes the heuristics but fails Matterhorn checkpoints
- WHEN validation runs
- THEN the findings contain `{checkId: "pdfua-conformance-failed", category: "accessibility"}` with the flavour and failed-checkpoint references in params
- AND the heuristic accessibility findings for the same document are also present, unchanged
- @e2e tests/e2e/spec-coverage/pdfua-verapdf-matterhorn.spec.ts

#### Scenario: Missing validator is an explicit finding, not a silent skip

- GIVEN a profile enabling `pdfua-conformance-failed` and no veraPDF binary on the instance
- WHEN validation runs on a PDF
- THEN the findings contain `{checkId: "accessibility-validator-unavailable", category: "accessibility", severity: "warning"}`
- AND no `pdfua-conformance-failed` finding is fabricated
- @e2e exclude degradation branch — covered by PHPUnit (tests/unit/Service/DocumentValidationServiceTest.php)

#### Scenario: Shipped defaults leave validator-backed accessibility checks off

- GIVEN an instance where the admin has never edited validation settings
- WHEN any PDF is validated
- THEN no `pdfua-conformance-failed` or `accessibility-validator-unavailable` finding is produced
- AND only the wave-1 heuristic accessibility findings (if any) appear
- @e2e exclude default-profile resolution — covered by PHPUnit (tests/unit/Service/DocumentValidationServiceTest.php)

#### Scenario: Escalated conformance check gates intake

- GIVEN a profile with `pdfua-conformance-failed` set to `blocking`, an available validator, and a non-conformant PDF upload
- WHEN intake runs
- THEN the existing 422 gate rejects it listing the accessibility finding
- @e2e exclude gate reuse without modification — covered by PHPUnit (tests/unit/Service/DocumentValidationServiceTest.php)
