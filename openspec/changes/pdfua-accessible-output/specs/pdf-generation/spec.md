# PDF Generation Specification (delta)

---
status: proposed
---

## Purpose

Extend the PDF generation capability with tagged, accessible output
(PDF/UA-1 target, ISO 14289-1 / EN 301 549 / WCAG 2.1 AA): an `accessible`
output option that routes rendering through a tagged-capable backend with
mandatory language and title metadata, and honest non-conformance reporting
on paths that cannot produce tagged output. Existing REQ-PDF-01..07
requirements are unchanged; this delta only ADDs requirements.

## ADDED Requirements

### Requirement: Accessible output routes through a tagged-capable backend (REQ-DDPUA-001)

The generation pipeline MUST support an `accessible: true` output option for
PDF output. When set, rendering MUST be produced by a backend capable of
tagged PDF: `LibreOfficeHeadlessBackend` MUST gain an accessible-export mode
adding `PDFUACompliance=true` to its `writer_pdf_Export` filter options
(keeping `UseTaggedPDF=true`), selected via the per-call options of
`PdfConversionService::convertToPdf()`. For Twig/HTML templates the rendered
HTML MUST be converted through this LibreOffice accessible mode instead of
mPDF; for office templates the filled DOCX takes the same path. Because mPDF
cannot emit structure tags, the mPDF path MUST NOT be used to satisfy an
`accessible: true` request and MUST NOT silently substitute untagged output:
when no tagged-capable backend is available the request MUST fail with a
structured conversion error carrying the per-backend attempt records. When
both `accessible` and PDF/A are requested the export MUST retain PDF/A
conformance alongside tagging.

#### Scenario: Accessible generation produces a tagged PDF

- GIVEN LibreOffice headless is available
- AND a template rendered with `accessible: true`
- WHEN the PDF is generated
- THEN the output contains a structure tree (`/StructTreeRoot`) and `/MarkInfo` with `/Marked true`
- AND the export used the `PDFUACompliance=true` filter mode
- @e2e tests/e2e/spec-coverage/pdfua-accessible-output.spec.ts

#### Scenario: Accessible request fails closed without a tagged-capable backend

- GIVEN LibreOffice headless is unavailable
- WHEN a generation with `accessible: true` is requested
- THEN the request fails with a structured error listing the attempted backends
- AND no untagged PDF is returned for the request
- @e2e exclude backend-outage fault injection is not browser-drivable; covered by PHPUnit (tests/unit/Service/PdfConversionServiceTest.php)

### Requirement: Accessible output carries mandatory language and title metadata (REQ-DDPUA-002)

Accessible PDF output MUST set the document language (`/Lang`) and the
document title metadata. The language MUST be resolved in this precedence:
an explicit per-call language option, the rendered template's language
variant, then the instance default locale; when no language is resolvable
the `accessible: true` generation MUST fail with a descriptive error rather
than emit output without `/Lang`. The title MUST come from the existing
`title` option or fall back to the template name. Semantic structure present
in the source (heading hierarchy, image alternative text, table headers)
MUST be preserved into the tagged output rather than flattened.

#### Scenario: Language and title are present in accessible output

- GIVEN a Dutch template rendered with `accessible: true` and title "Besluit parkeervergunning"
- WHEN the PDF is generated
- THEN the PDF catalog carries `/Lang` with the Dutch language tag
- AND the document title metadata equals "Besluit parkeervergunning"
- @e2e tests/e2e/spec-coverage/pdfua-accessible-output.spec.ts

#### Scenario: Unresolvable language fails the accessible request

- GIVEN a template with no language variant, no explicit language option, and no resolvable instance locale
- WHEN generation with `accessible: true` is requested
- THEN the request fails naming the missing language
- AND no PDF without `/Lang` is produced for the request
- @e2e exclude locale-resolution permutation; covered by PHPUnit (tests/unit/Service/PdfServiceTest.php)

#### Scenario: Heading structure survives into the tag tree

- GIVEN a template whose content uses h1/h2 headings and an image with alt text
- WHEN an accessible PDF is generated
- THEN the structure tree contains corresponding heading elements in order
- AND the image carries the alternative text
- @e2e exclude tag-tree introspection requires a PDF parser in the test harness; covered by PHPUnit against the tagged fixture set (tests/unit/Service/PdfConversionServiceTest.php)
