# Design: verapdf-validation

## Context

Verified at HEAD (worktree `spec/market-gap-wave2-2026-07`, includes the nine
wave-1 changes):

- `Pdfa3ConversionService::validateOutput()` is a string scan for
  `pdfaid:part`/`pdfaid:conformance`; on failure it raises
  `Pdfa3ConversionException` reason `output_validation_failed` (the
  fail-loud contract of the canonical `pdfa3-conversion` spec). The service
  configures mPDF font embedding for its own rendering
  (`PdfService::getFontDirectory()`, DejaVu fallback), but
  `convertExistingPdf()` imports pages as opaque XObjects — fonts inside
  imported pages are whatever the source embedded (CB #182 limitation 2).
- `DocumentValidationService`: stable check-id catalogue, per-profile
  severities (`off|warning|blocking`), parser-free byte heuristics, verdict
  aggregation, on-demand non-persisting endpoint; wave-1 REQ-DDPUA-003 added
  the `category` finding key (`accessibility`, default `document`) and the
  UI groups findings by category. Config lives at
  `docudesk.validation.profiles`.
- Optional-binary precedent: `LibreOfficeHeadlessBackend::isAvailable()`
  (soffice) and `OcrService::isTesseractAvailable()` +
  `getTesseractVersion()` — probe, admin-settings status display, feature
  degrades honestly when absent. veraPDF fits this pattern exactly.
- `docs/features/eml-pdf-assembly.md` already instructs manual
  `verapdf --format text --validate-profile 3b` runs — the integration is
  currently a human.

## Goals / Non-Goals

**Goals:**

- Real PDF/A-1b/2b/3b conformance verdicts, locally computed, stored per
  document, and reachable through the existing validation surface.
- Font-embedding truth with actionable remediation guidance.
- Output verification for `pdfa3-conversion` so DocuDesk stops shipping
  unverified conformance claims when a validator is present.

**Non-Goals:**

- No PDF/UA (Matterhorn) validation — veraPDF can do it, but wave-1's
  accessibility heuristics own that surface; extending them is a separate
  decision (noted as follow-up, not smuggled in here).
- No PDF/A *remediation engine* — this change verifies and guides;
  re-rendering/re-conversion remains the job of the existing generation and
  conversion paths.
- No bundling of veraPDF (Java runtime, ~100 MB distribution) into the app
  or the composer tree; no CI-corpus work (CB #182 mentions CI — that is
  repo tooling, not app behaviour, and stays out of an app spec).
- No A-level (`-1a/-2a/-3a`) or PDF/A-4 targets in v1 — b-level matches
  what DocuDesk generates and what e-depots demand at minimum.

## Decisions

### D1 — veraPDF CLI, not PDFBox/JHOVE, not a PHP library

**Chosen: veraPDF invoked as a local CLI binary.** Justification:

- It is the reference implementation, developed with the PDF Association
  for ISO 19005 — its verdicts are what e-depots and auditors trust; "does
  veraPDF pass" is the acceptance criterion CB #182 states.
- Full coverage of PDF/A-1/2/3 at all levels from one tool, with
  machine-readable output (`--format json`/XML: flavour, per-rule failures
  with specification/clause references) — maps 1:1 onto findings.
- Open source (dual MPL/GPL), runs fully offline, fits the
  soffice/Tesseract optional-binary pattern already shipped.

**Rejected:** Apache PDFBox preflight — Java too, but PDF/A-1b-centric and
not the reference tool; JHOVE — format identification with weaker,
dated PDF/A profiling; a pure-PHP validator — none exists with credible ISO
19005 coverage; smuggling validation into mPDF/FPDI — those are producers,
not validators, and self-certification is the problem being fixed.

Consequence (accepted): a Java runtime on the server for instances that opt
in. The binary path (`docudesk.verapdf.binary_path`, default `verapdf` on
PATH), wall-clock budget (`docudesk.verapdf.max_seconds`, default 60) and an
enable toggle (`docudesk.verapdf.enabled`, default on-when-found) are app
config; the admin settings page shows probe status + version next to the
existing soffice/Tesseract rows.

### D2 — Validation contract: validate the claim, then the request

`VeraPdfService::validate(File $file, ?string $flavour = null)`:

- When `$flavour` is null, the flavour is taken from the document's own
  `pdfaid` claim (validate what you claim — the CB #182 posture); a
  document claiming nothing validates against the requested or default
  profile (`3b`).
- Result: `{flavour, compliant: bool, failedRules:
  list<{ruleId, specification, clause, testNumber, checksFailed}>,
  validatorVersion, elapsedMs}` parsed from veraPDF JSON output. Rule
  entries carry references only — never document content.
- Timeout/crash/unparseable output → a typed `VeraPdfException`; callers
  degrade per their own contracts (checks emit the unavailable finding,
  pdfa3 strict mode fails loud, report endpoint returns 503-style error).
  A validator error is NEVER recorded as "compliant".

### D3 — `document-validation-checks`: the `archival` category

New check ids in `DocumentValidationService`, same catalogue/profile/
severity machinery, grouped as `category: "archival"` (sibling of wave-1's
`accessibility`):

| checkId | Backed by |
|---|---|
| `pdfa-conformance-failed` | veraPDF verdict for the claimed/requested flavour; finding params carry flavour + failed-rule count + top rule references |
| `pdfa-font-not-embedded` | veraPDF font rules (the CB #182 e-depot trap), reported even when the rest passes; params name the fonts |
| `archival-validator-unavailable` | emitted (severity `warning`, non-escalatable) when an archival check is enabled in the profile but veraPDF is absent/disabled — an honest "not validated" instead of a silent skip |

All default to `warning` in shipped profiles ("default deployment never
blocks"); admins escalate `pdfa-conformance-failed` to `blocking` through
the existing profile mechanism, which then rides the existing 422 gate.
These checks run ONLY on PDFs and only when the profile enables them —
validation cost (JVM start, ~1-3 s/doc) is why they are per-profile opt-in
rather than default-on for every upload. The existing heuristics
(`pdfaid` markers, wave-1 accessibility checks) are untouched: heuristics
remain the always-available floor, the validator is the opt-in truth.

### D4 — Conformance report: a persisted `conformanceReport` OR object

The validation-checks path stores findings, but archival evidence needs the
full verdict (rule list, validator version) attached to the document and
re-runnable on demand — e-depot hand-off wants "prove it", not a chip.
**Decision:** new `conformanceReport` schema keyed by `fileId` (re-running
updates it, mirroring `ocrResult`): `flavour`, `compliant`,
`failedRuleCount`, `failedRules` (bounded summary list of rule references),
`fontsNotEmbedded` (font names), `validatorVersion`, `validatedAt`,
`trigger` (`manual` | `validation` | `conversion`). Content-free by
construction (rule references and font names only). Endpoint:
`POST /api/validation/conformance/{fileId}` (IDOR-safe user-folder
resolution, mirrors the existing on-demand validation endpoint but DOES
persist the report — that persistence is its purpose). Rejected: stuffing
the rule list into `validationFindings` — findings are per-check summaries;
a 60-rule dump would bloat every verdict read.

### D5 — `pdfa3-conversion` output verification: floor stays, truth added

When veraPDF is available, `Pdfa3ConversionService` verifies assembled
output after the existing marker guard:

- **Default (report mode):** the verdict is returned on the composition
  metadata surface (an `X-Docudesk-Pdfa3-Verified: true|false|skipped`
  response header next to the existing checksum/pages/conformance headers)
  and persisted as a `conformanceReport` with `trigger: "conversion"`; a
  non-compliant verdict logs a warning but returns the bytes — matching
  the current behavioural envelope so conversion never breaks on validator
  adoption day.
- **Strict (`docudesk.pdfa3.strict_verify`, default false):** a
  non-compliant verdict raises the existing
  `Pdfa3ConversionException` reason `output_validation_failed` — the
  spec's no-silent-passthrough contract, now with real teeth.
- veraPDF absent → header `skipped`; the heuristic marker guard remains the
  floor (unchanged, always on).

`convertExistingPdf()` outputs that fail ONLY on font rules get the
remediation guidance of D6 attached to the report — the known, documented
limitation surfaces as information, not as a mystery failure.

### D6 — Remediation guidance is generated from the failure shape

Guidance strings (i18n EN/NL) keyed by failure class, attached to the
report and the findings panel:

- Font failures + document produced by `convertHtml()`/the LO cascade →
  "regenerate through DocuDesk" (its paths embed fonts; a stale artifact
  predates font config).
- Font failures + document produced by `convertExistingPdf()` or uploaded →
  "DocuDesk cannot retroactively embed fonts in imported pages (CB #182);
  re-convert from the original source file, or accept e-depot rejection
  risk" — honest limitation surfacing.
- Non-font rule failures → the rule's specification/clause reference plus
  the generic re-generate advice.

No auto-remediation in v1 (Non-Goals).

### D7 — Declarative vs imperative (ADR-031), OR usage (ADR-001), frontend (ADR-012)

`conformanceReport` is a **declarative** register addition (single-state
evidence record, no lifecycle). Validator invocation, checks, and the
conversion hook are **imperative** — external-binary document processing,
the established ADR-031 exception (same category as soffice/Tesseract).
Persistence via ObjectService/AppHost (no custom tables); OR services
otherwise untouched. ADR-011: no new parsing utilities beyond the veraPDF
JSON reader in one service. Frontend: `archival` group in the existing
category-grouped findings panel; conformance card on document detail
(flavour, verdict, fonts, guidance); admin status row; NC CSS variables
(ADR-003); strings EN-keyed with NL translations (ADR-005).

## Seed Data

```json
// conformanceReport — a compliant generated besluit
{ "fileId": 814001,
  "flavour": "3b",
  "compliant": true,
  "failedRuleCount": 0,
  "failedRules": [],
  "fontsNotEmbedded": [],
  "validatorVersion": "veraPDF 1.26.2",
  "validatedAt": "2026-07-17T10:12:00Z",
  "trigger": "conversion" }

// conformanceReport — an imported PDF failing on fonts (the CB #182 trap)
{ "fileId": 814002,
  "flavour": "3b",
  "compliant": false,
  "failedRuleCount": 2,
  "failedRules": [
    { "ruleId": "ISO19005-3_6.2.11.4-1", "specification": "ISO 19005-3:2012", "clause": "6.2.11.4", "checksFailed": 14 } ],
  "fontsNotEmbedded": [ "Arial", "Calibri" ],
  "validatorVersion": "veraPDF 1.26.2",
  "validatedAt": "2026-07-17T10:14:30Z",
  "trigger": "manual" }
```

Test fixtures (committed under `tests/sample-documents/`, generated content,
no personal data): a genuinely conformant PDF/A-3b file, a marker-claiming
but rule-failing file, and a non-embedded-font import wrapped by
`convertExistingPdf()`.

## Risks / Trade-offs

- [JVM cost per validation (~1-3 s + memory)] → per-profile opt-in checks
  (D3), on-demand endpoint, wall-clock budget, and no default-on upload
  validation; bulk validation rides `redaction-at-scale`-style ticks if
  ever needed (out of scope here).
- [veraPDF JSON schema drift across versions] → parser pins the fields it
  reads and fails typed (D2); probe records the version; fixtures pin one
  known-good version in CI.
- [Report mode still ships non-compliant bytes] → deliberate default for
  adoption safety; the verdict is persisted + surfaced, and strict mode is
  one config flip; the marker floor never regresses.
- [Two truths (heuristic vs validator) can disagree] → the UI labels the
  source: heuristic findings stay category `document`/`accessibility`,
  validator findings are `archival`; docs explain the hierarchy.
- [Admins without Java lose nothing but gain warnings] →
  `archival-validator-unavailable` only fires when a profile *enables*
  archival checks; the shipped default profiles leave them off... **no** —
  shipped defaults set every check to `warning`, but these checks are
  additionally gated on validator presence, so absent-Java instances see no
  noise unless an admin enabled archival checks explicitly (scenario-locked).

## Migration Plan

1. Register JSON: add `conformanceReport` (additive, union-merge).
2. `VeraPdfService` + probe + admin status row (inert without binary).
3. `archival` checks + conformance endpoint + document-detail card.
4. `pdfa3-conversion` verification hook (report mode), then strict-mode
   config documentation.
5. Rollback: disable via `docudesk.verapdf.enabled` or remove the binary —
   everything degrades to today's heuristic behaviour; reports remain as
   inert evidence; no data migration.

## Open Questions

- Should `eml-pdf-assembly` output auto-validate when veraPDF is present
  (its docs currently prescribe the manual command)? Provisional: yes via
  the same conversion hook once it routes through `Pdfa3ConversionService`;
  not spec'd here to avoid touching that capability.
- PDF/UA validation via veraPDF as an upgrade to the wave-1 accessibility
  heuristics — follow-up decision with the pdfua capability owner; the
  category naming here (`archival` vs `accessibility`) deliberately leaves
  that door open.
