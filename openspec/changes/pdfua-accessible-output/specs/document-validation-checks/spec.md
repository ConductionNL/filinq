# document-validation-checks Specification (delta)

---
status: proposed
---

## Purpose

Extend the document validation check catalogue with an accessibility
category: heuristic, parser-free PDF/UA/WCAG presence checks reported per
document through the existing profile/severity/verdict mechanism. Existing
requirements (catalogue, profiles, calculation storage, on-demand endpoint,
422 gate, UI) are unchanged; this delta only ADDs requirements.

## ADDED Requirements

### Requirement: The check catalogue MUST include an accessibility category (REQ-DDPUA-003)

`DocumentValidationService` MUST implement four additional checks, grouped
under a new optional finding key `category: "accessibility"` (existing
findings default to category `document`; the aggregation of
`validationStatus` is unchanged):

- `pdf-not-tagged` — the PDF carries no `/StructTreeRoot` reference, or its
  `/MarkInfo` lacks `/Marked true`.
- `pdf-language-missing` — the PDF catalog carries no `/Lang` entry.
- `pdf-title-missing` — neither XMP `dc:title` nor Info `/Title` has a
  non-empty value.
- `pdfua-identifier-missing` — the XMP metadata lacks a `pdfuaid:part`
  identifier; this finding MUST be suppressed when `pdf-not-tagged` already
  fired for the same document.

The checks MUST be heuristic byte-level scans in the style of the existing
`pdf-encrypted`/`text-layer-missing` checks (no new parsing dependency),
MUST apply only to PDF content, MUST ride the existing
`filinq.validation.profiles` per-check severity mechanism
(`off|warning|blocking`), MUST default to `warning` in every shipped
profile, and — like all findings — MUST never embed document content. The
service and its documentation MUST describe these as accessibility presence
heuristics, not certified PDF/UA (Matterhorn/veraPDF-grade) validation.

#### Scenario: Untagged PDF fires the accessibility findings

- GIVEN an untagged PDF generated via mPDF
- WHEN validation runs under a default profile
- THEN the findings contain `{checkId: "pdf-not-tagged", severity: "warning", category: "accessibility"}`
- AND no `pdfua-identifier-missing` finding is produced for it
- @e2e tests/e2e/spec-coverage/pdfua-accessible-output.spec.ts

#### Scenario: Tagged PDF without language fires only the language check

- GIVEN a tagged PDF fixture whose catalog lacks `/Lang`
- WHEN validation runs
- THEN the findings contain `pdf-language-missing`
- AND contain neither `pdf-not-tagged` nor a false `pdf-title-missing` when a title is present
- @e2e exclude fixture-permutation matrix; covered by PHPUnit (tests/unit/Service/DocumentValidationServiceTest.php)

#### Scenario: Accessible fixture passes the accessibility category

- GIVEN the tagged PDF/UA fixture with `/StructTreeRoot`, `/Lang`, a title, and `pdfuaid:part`
- WHEN validation runs
- THEN no accessibility-category finding is produced
- @e2e exclude pure service computation; covered by PHPUnit (tests/unit/Service/DocumentValidationServiceTest.php)

#### Scenario: Admin escalates an accessibility check to blocking

- GIVEN a profile setting `pdf-not-tagged` to `blocking`
- WHEN an untagged PDF is validated
- THEN `validationStatus` is `failed` via the existing aggregation
- AND the existing intake 422 gate applies without any new gating mechanism
- @e2e exclude severity-escalation config permutation; covered by PHPUnit (tests/unit/Service/DocumentValidationServiceTest.php)
