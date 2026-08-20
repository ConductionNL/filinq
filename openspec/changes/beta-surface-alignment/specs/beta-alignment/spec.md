# beta-alignment Specification (delta)

---
status: proposed
---

## Purpose

Guarantee that DocuDesk's four public surfaces — `appinfo/info.xml`,
`src/manifest.json` nav, the conduction.nl product page (EN + NL), and the
docudesk.conduction.nl docs site — describe the same feature vocabulary, the
same version/status, and only claims that are verifiable against `lib/` at
HEAD. An app cannot be beta-released while any public surface asserts a
feature, standard, or integration that does not exist in code.

## ADDED Requirements

### Requirement: Every marketing or compliance claim on a public surface
MUST be verifiable against `lib/` at HEAD

A claim about a feature, integration, standard, or compliance capability
(e.g. "supports Presidio," "TMLO-compliant archiving," "SharePoint
integration," "WCAG 2.1 AAA") that appears on the product page, in
`appinfo/info.xml`, or in `docs/` MUST correspond to code that actually
implements it. A claim with no corresponding implementation MUST be removed
or rewritten to describe what is actually implemented.

#### Scenario: A claim references a service that does not exist in lib/

- GIVEN a docs page asserts `$wcagService->checkCompliance(...)` performs
  WCAG document validation
- AND no `wcagService`, WCAG-related class, or WCAG string reference exists
  anywhere under `lib/`
- WHEN the beta-alignment audit is run
- THEN the claim SHALL be flagged as fabricated and removed or corrected to
  state the actual (unimplemented) status

#### Scenario: A claim about output format matches the code's supported set

- GIVEN `DocumentService::VALID_FORMATS` defines the supported document
  generation output formats
- WHEN a product-page or docs claim describes document generation output
  formats
- THEN the claim SHALL list only formats present in `VALID_FORMATS` (PDF,
  ODF, HTML) and SHALL NOT claim unsupported formats (e.g. Word/.docx,
  Excel/.xlsx)

### Requirement: info.xml, product page, and docs MUST agree on version and release status

The version string and release-status label MUST be derived from
`appinfo/info.xml`'s `<version>` wherever they are shown (e.g. "Beta"/"Stable"
on the product page and in docs), not an independently invented number or status.

#### Scenario: Product page version tracks info.xml

- GIVEN `appinfo/info.xml` declares `<version>0.0.34-unstable.18</version>`
- WHEN the conduction.nl product page renders a `version` prop
- THEN it SHALL display a value derived from `0.0.34` (the info.xml version),
  not an unrelated fabricated version

#### Scenario: Pre-1.0 apps are labelled Beta, not Stable

- GIVEN an app's `appinfo/info.xml` version major.minor is `0.x`
- AND the app's `docs/features.json` contains one or more entries whose
  summary states the feature is `not yet shipped`
- WHEN the product page renders a `status` label
- THEN it SHALL be `Beta`, not `Stable`

### Requirement: info.xml summary MUST be present in both English and Dutch, with Dutch being a real translation

Per ADR-007, `appinfo/info.xml` MUST declare `<summary lang="en">` and
`<summary lang="nl">`, and the Dutch summary MUST be an actual Dutch
translation, not the English string copied verbatim or machine-transliterated.

#### Scenario: info.xml has a single language-less summary

- GIVEN `appinfo/info.xml` contains `<summary>` with no `lang` attribute
- WHEN the beta-alignment audit is run
- THEN it SHALL be flagged and split into `<summary lang="en">` and
  `<summary lang="nl">`, with the Dutch text being a genuine translation

### Requirement: info.xml documentation links MUST point to a live, current docs site

`appinfo/info.xml`'s `<documentation>` block MUST link to the docs site that
is actually deployed and current, not a decommissioned or superseded
location.

#### Scenario: info.xml points at a dead docs host

- GIVEN `appinfo/info.xml` `<documentation>` links to
  `conduction.gitbook.io/docudesk-nextcloud/`
- AND the app's `docs/` directory is a live Docusaurus site deployed at
  `docudesk.conduction.nl`
- WHEN the beta-alignment audit is run
- THEN the `<documentation>` links SHALL be updated to the live site
