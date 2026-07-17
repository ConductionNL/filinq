# document-validation-checks Specification (delta)

---
status: proposed
---

## Purpose

Extend the document validation check catalogue with an `archival` category:
validator-backed PDF/A conformance and font-embedding checks (veraPDF),
riding the existing profile/severity/verdict mechanism, with an honest
unavailable-validator finding instead of a silent skip. Wave-1's
`accessibility` category (REQ-DDPUA-003) and its category mechanism are
reused unchanged. The profiles requirement is MODIFIED only in its
shipped-defaults sentence: validator-backed checks default `off` (explicit
admin opt-in) because a `warning` default would emit unavailable-validator
noise on every instance without the binary.

## ADDED Requirements

### Requirement: The check catalogue MUST include a validator-backed archival category (REQ-DDVPV-005)

`DocumentValidationService` MUST implement archival checks grouped under
finding category `archival` (sibling of `accessibility`; existing findings
keep their categories), riding the existing catalogue/profile/severity
mechanism:

- `pdfa-conformance-failed` — veraPDF reports the PDF non-compliant with
  its claimed (or the profile-requested) PDF/A flavour; finding params carry
  the flavour, the failed-rule count and the top rule references
  (`verapdf-validation` REQ-DDVPV-002).
- `pdfa-font-not-embedded` — veraPDF font rules fail; finding params name
  the fonts (REQ-DDVPV-003).
- `archival-validator-unavailable` — an archival check is enabled in the
  resolved profile but the validator is absent or disabled; an explicit
  "not validated" finding so absence is visible, never a silent skip.

The archival checks MUST run only for PDF files and only when the resolved
profile enables them (per-profile opt-in — validator invocations cost a JVM
start per document). Findings MUST carry rule references and font names only
— never document content. Aggregation (`validationStatus`) and the 422
blocking gate apply to archival findings exactly as to existing checks.

#### Scenario: Non-conformant PDF fires the archival finding

- GIVEN a profile with `pdfa-conformance-failed` at severity `warning`, an available validator, and a PDF claiming PDF/A-3b that violates ISO 19005-3 rules
- WHEN validation runs
- THEN the findings contain `{checkId: "pdfa-conformance-failed", category: "archival"}` with the flavour and failed-rule references in params
- @e2e tests/e2e/spec-coverage/verapdf-validation.spec.ts

#### Scenario: Missing validator is an explicit finding, not a silent skip

- GIVEN a profile enabling `pdfa-conformance-failed` and no veraPDF binary on the instance
- WHEN validation runs on a PDF
- THEN the findings contain `{checkId: "archival-validator-unavailable", category: "archival", severity: "warning"}`
- AND no `pdfa-conformance-failed` or `pdfa-font-not-embedded` finding is fabricated
- @e2e exclude degradation branch — covered by PHPUnit (tests/unit/Service/DocumentValidationServiceTest.php)

#### Scenario: Escalated conformance check gates intake

- GIVEN a profile with `pdfa-conformance-failed` set to `blocking` and a non-conformant PDF upload
- WHEN intake runs
- THEN the existing 422 gate rejects it listing the archival finding
- @e2e exclude gate reuse without modification — covered by PHPUnit (tests/unit/Service/DocumentValidationServiceTest.php)

## MODIFIED Requirements

### Requirement: Validation profiles MUST be configurable per document type with per-check severity

Profiles MUST live in app config `docudesk.validation.profiles`: per
document type an allowed-mime list, required metadata fields, and a severity
per check from `off | warning | blocking`. Unknown document types MUST resolve
to the `default` profile. Shipped defaults MUST set every content and
metadata check to `warning` and every validator-backed `archival`-category
check to `off` (no blocking out of the box; validator-backed checks are an
explicit admin opt-in so instances without the validator binary see no
unavailable-validator noise). Profile reads happen at validation time so
config changes propagate without restart.

@e2e exclude Profile resolution, per-check severity, default fallback, off-skip — config-driven service logic. Covered by PHPUnit (DocumentValidationServiceTest).

#### Scenario: Default deployment never blocks

- **GIVEN** an instance where the admin has never edited validation settings
- **WHEN** any file is uploaded, however broken
- **THEN** intake proceeds (no 422 from validation)
- **AND** findings are recorded with severity `warning` at most

#### Scenario: Per-type profile resolution

- **GIVEN** a profile for document type `factuur` requiring field `invoiceNumber`
- **AND** a record of type `factuur` without `invoiceNumber`
- **WHEN** validation runs
- **THEN** `metadata-incomplete` fires for `invoiceNumber`
- **AND** records of other types are not checked against `invoiceNumber`

#### Scenario: Unknown type falls back to the default profile

- **GIVEN** a record whose document type matches no configured profile
- **WHEN** validation runs
- **THEN** the `default` profile's checks apply

#### Scenario: A check set to off is skipped

- **GIVEN** a profile with `extension-mime-mismatch` set to `off`
- **AND** a file with a mismatching extension
- **WHEN** validation runs
- **THEN** no `extension-mime-mismatch` finding is produced

#### Scenario: Shipped defaults leave archival checks off

- **GIVEN** an instance where the admin has never edited validation settings
- **WHEN** any PDF is validated
- **THEN** no `archival`-category finding of any kind is produced
- @e2e exclude default-profile resolution — covered by PHPUnit (tests/unit/Service/DocumentValidationServiceTest.php)
