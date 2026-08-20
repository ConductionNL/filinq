# pdfua-accessible-output Specification (delta)

---
status: proposed
---

## Purpose

The cross-cutting accessibility surface on top of accessible generation
(pdf-generation delta) and the accessibility check category
(document-validation-checks delta): accessibility status surfaced in the
validation UI, a publication-readiness warning before Woo hand-off, and
template-level accessibility lint in preview. Statutory frame: Besluit
digitale toegankelijkheid overheid / EU Directive 2016/2102 / EN 301 549 →
WCAG 2.1 AA; target output profile PDF/UA-1 (ISO 14289-1).

## ADDED Requirements

### Requirement: Accessibility status is surfaced in the validation UI (REQ-DDPUA-004)

The validation findings UI MUST group findings by category and MUST render
an `accessibility` section with localised messages when accessibility
findings exist; the existing verdict chip semantics stay unchanged. The
wording MUST describe the state as accessibility checks (e.g. "accessibility
checks passed" / "openstaande toegankelijkheidsbevindingen") and MUST NOT
claim PDF/UA certification. All new UI MUST itself satisfy WCAG 2.1 AA and
use NL Design System tokens via Nextcloud CSS variables.

#### Scenario: Operator sees grouped accessibility findings

- GIVEN a document with `pdf-not-tagged` and `pdf-language-missing` findings
- WHEN the operator opens its validation results
- THEN an "Accessibility" group lists both findings with localised explanations
- AND the verdict chip reflects the existing aggregation
- @e2e tests/e2e/spec-coverage/pdfua-accessible-output.spec.ts

### Requirement: Open accessibility findings warn before Woo publication hand-off (REQ-DDPUA-005)

Publication-facing actions in DocuDesk MUST consult a
publication-readiness signal derived from the document's stored validation
findings: when open `accessibility`-category findings exist, the action MUST
show a warning naming the findings (with a link to the validation detail)
before the user proceeds. The warning MUST NOT hard-block by itself —
blocking remains the domain of the existing profile severity mechanism
(escalating a check to `blocking` engages the standard intake/verdict gate).
The signal MUST be computed within DocuDesk; publication endpoints remain
owned by OpenCatalogi/OpenWoo and any publication pipeline consumes the same
signal.

#### Scenario: Inaccessible document warns before publication

- GIVEN a document prepared for Woo publication whose findings include `pdf-not-tagged`
- WHEN the user triggers the publication hand-off action
- THEN a warning states the document has open accessibility findings and links to them
- AND the user can still proceed unless a blocking severity applies
- @e2e tests/e2e/spec-coverage/pdfua-accessible-output.spec.ts

#### Scenario: Clean document proceeds without warning

- GIVEN a document whose accessibility-category findings are empty
- WHEN the publication hand-off action is triggered
- THEN no accessibility warning is shown
- @e2e tests/e2e/spec-coverage/pdfua-accessible-output.spec.ts

### Requirement: Template preview lints accessibility at authoring time (REQ-DDPUA-006)

Template preview MUST run an accessibility lint over the rendered preview
(HTML for Twig templates; the text/XML projection for office templates) and
MUST return the lint results alongside the preview: images without
non-empty alternative text, heading-order jumps (e.g. h1 followed by h3
without h2), no resolvable document language, and (HTML path) tables
without header cells. Lint results MUST render as a non-blocking checklist
in the template editor and MUST NOT prevent saving or previewing — document
validation remains the enforcement point. Lint findings MUST reference the
offending element positionally (e.g. nth image, heading text) and MUST NOT
require the author to read generated output to locate the problem.

#### Scenario: Author sees a missing-alt lint in preview

- GIVEN a template containing an image without alt text and an h1→h3 heading jump
- WHEN the author opens the template preview
- THEN the lint checklist reports the missing alternative text and the heading-order jump, each locating the offending element
- AND the template can still be saved and previewed
- @e2e tests/e2e/spec-coverage/pdfua-accessible-output.spec.ts

#### Scenario: Clean template lints empty

- GIVEN a template with alt-texted images, ordered headings, and a language variant
- WHEN preview runs
- THEN the lint checklist is empty
- @e2e exclude lint-computation permutations; covered by PHPUnit (tests/unit/Service/TemplatePreviewServiceTest.php)
