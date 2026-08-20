# pdfa3-conversion Specification (delta)

---
status: proposed
---

## Purpose

Add real output verification to PDF/A-3 conversion: when veraPDF is
available, `Pdfa3ConversionService` validates its assembled output against
ISO 19005 instead of only asserting its own XMP claim markers. Report mode
(default) persists and surfaces the verdict without changing the conversion
envelope; strict mode makes the spec's existing fail-loud contract real.
Existing requirements — including the marker-guard requirement, which
remains the always-on floor — are unchanged; this delta only ADDs a
requirement.

## ADDED Requirements

### Requirement: Conversion output is verified with veraPDF when available (REQ-DDVPV-006)

`Pdfa3ConversionService` MUST, when the veraPDF backend is available
(`verapdf-validation` REQ-DDVPV-001), validate the assembled output against
the produced flavour (3-B) after the existing marker guard, and MUST expose
the verdict on the composition-metadata surface as an
`X-Docudesk-Pdfa3-Verified` response header (`true` | `false` | `skipped`)
next to the existing checksum/pages/conformance headers, persisting the
result as a `conformanceReport` with `trigger: "conversion"`
(REQ-DDVPV-004). Behaviour by mode:

- **Report mode (default):** a non-compliant verdict logs a warning,
  ships the bytes unchanged (adoption-safe), and leaves the persisted
  report + header as the truth.
- **Strict mode (`docudesk.pdfa3.strict_verify`, default `false`):** a
  non-compliant verdict MUST raise `Pdfa3ConversionException` with reason
  `output_validation_failed` and return no bytes — the existing
  no-silent-passthrough contract, now backed by a real validator.
- **Validator absent:** the header reads `skipped`; the heuristic marker
  guard remains in force unchanged.

Outputs of `convertExistingPdf()` that fail only on font-embedding rules
MUST carry the imported-pages remediation guidance (REQ-DDVPV-003) on the
persisted report — the documented retroactive-font limitation surfaces as
information, not as a mystery failure.

#### Scenario: Verified conversion exposes the verdict header

- GIVEN an available validator and a successful `POST /api/pdfa3/convert`
- WHEN the response is returned
- THEN it carries `X-Docudesk-Pdfa3-Verified: true`
- AND a `conformanceReport` with `trigger: "conversion"` exists for the output
- @e2e exclude header/persistence contract — covered by PHPUnit (tests/unit/Controller/Pdfa3ConversionControllerTest.php)

#### Scenario: Strict mode refuses non-compliant output

- GIVEN `docudesk.pdfa3.strict_verify` enabled and an imported source whose wrapped output fails ISO 19005-3 font rules
- WHEN conversion runs
- THEN it raises `Pdfa3ConversionException` with reason `output_validation_failed`
- AND no bytes are returned to the caller
- @e2e exclude strict-mode guard — covered by PHPUnit (tests/unit/Service/Pdfa3ConversionServiceTest.php)

#### Scenario: Report mode ships bytes but records the failure

- GIVEN default report mode and the same font-failing conversion
- WHEN conversion runs
- THEN the PDF is returned with `X-Docudesk-Pdfa3-Verified: false`
- AND the persisted report carries the font names and the imported-pages remediation guidance
- @e2e tests/e2e/spec-coverage/verapdf-validation.spec.ts

#### Scenario: No validator keeps today's envelope

- GIVEN no veraPDF binary on the instance
- WHEN conversion runs successfully
- THEN the response carries `X-Docudesk-Pdfa3-Verified: skipped`
- AND the existing marker guard behaviour is unchanged
- @e2e exclude degradation branch — covered by PHPUnit (tests/unit/Service/Pdfa3ConversionServiceTest.php)
