# DocuDesk Features

This directory contains documentation for all DocuDesk features: implemented, planned, and proposed. Each `.md` file maps to one functional area and is rendered by the Docusaurus site.

## Standards References

DocuDesk is designed against Dutch government standards and open European norms. The table below lists the applicable standards per functional layer.

### Dutch Government Standards (Forum Standaardisatie)

| Standard | Scope | Applies To |
|----------|-------|------------|
| [PDF (NEN-ISO 32000)](https://www.forumstandaardisatie.nl/open-standaarden/pdf) | Documentbeheer — Draagbaar documentformaat | PDF generation, print, document signing |
| [PDF/UA (ISO 14289-1)](https://www.forumstandaardisatie.nl/open-standaarden/pdf-ua) | Accessible PDF for public procurement | PDF generation, WCAG compliance |
| [Digitoegankelijk EN 301 549 / WCAG 2.1](https://www.forumstandaardisatie.nl/open-standaarden/digitoegankelijk) | Accessibility requirements for ICT products and services | All UI components |
| [XML](https://www.forumstandaardisatie.nl/open-standaarden/xml) | Extensible Markup Language | Data export, template rendering |
| [CSV](https://www.forumstandaardisatie.nl/open-standaarden/csv) | Common Format and MIME Type for CSV | Audit report export |
| [RDF](https://www.forumstandaardisatie.nl/open-standaarden/rdf) | Resource Description Framework | Metadata enrichment |

### EU / International Standards

| Standard | Scope | Applies To |
|----------|-------|------------|
| GDPR / AVG | Privacy-by-design data processing | Anonymization, consent management |
| Wet Open Overheid (WOO) | Publication objection periods (min. 4 weeks) | Consent management |
| eIDAS | Electronic identification and trust services | Document signing |
| PAdES (ETSI EN 319 100) | PDF Advanced Electronic Signatures | Document signing |
| RFC 3161 | Trusted timestamps | Document signing |
| ISO/IEC 27001 | Information security management | Security, admin settings |
| PDF/A-3b (ISO 19005-3) | Long-term archival PDF | Print/PDF generation |
| WCAG 2.1 AA | Web Content Accessibility Guidelines | All UI, consent workflows |

### GEMMA Architecture References

DocuDesk implements capabilities from the following [GEMMA](https://gemmaonline.nl) reference components:

| GEMMA Component | URL | DocuDesk Feature |
|-----------------|-----|------------------|
| Documentregistratiecomponent | [GEMMA](https://gemmaonline.nl/index.php/GEMMA/id-0e99ec6c-283a-4ec9-8efa-e11468e6b878) | Document register, metadata enrichment |
| Outputmanagementcomponent | [GEMMA](https://gemmaonline.nl/index.php/GEMMA/id-15064617-043a-4b22-bc68-718d915bcfc1) | PDF generation, template management, correspondence generation |
| Documentbeheercomponent | [GEMMA](https://gemmaonline.nl/index.php/GEMMA/id-25ee9ea7-be66-4bdd-b40c-191777a88b35) | Document register, consent management |
| Scanning-en-imagingcomponent | [GEMMA](https://gemmaonline.nl/index.php/GEMMA/id-89d557be-4c18-464e-b5fd-4f56c66c8b66) | OCR document scanning |
| Documentcreatiecomponent | [GEMMA](https://gemmaonline.nl/index.php/GEMMA/id-d6a2d1a8-23be-4808-b5ac-69e00de528c9) | Template management, document creation from templates |
| Media-behandelingcomponent | [GEMMA](https://gemmaonline.nl/index.php/GEMMA/id-4aa05fa5-22eb-4d9b-869b-3f61312f0257) | Anonymization pipeline, text extraction |

### TEC Feature Framework (DMS)

DocuDesk covers the following top-level TEC DMS feature categories:

| TEC Code | Category | DocuDesk Feature |
|----------|----------|------------------|
| TEC-DMS-1 | Content Authoring | Template management, advanced template management |
| TEC-DMS-2 | Content Acquisition | OCR scanning, text extraction |
| TEC-DMS-4 | Document and Records Management | Document register, metadata enrichment |
| TEC-DMS-5 | Security Management | Admin settings, consent management |
| TEC-DMS-7 | Workflow Management | Consent process, batch anonymization |
| TEC-DMS-8 | Version Control and Management | Advanced template management |
| TEC-DMS-9 | Search and Indexing Management | Metadata enrichment |
| TEC-DMS-10 | Reporting and Statistics Management | Prometheus metrics, dashboard |

## Feature Overview

### Implemented

Features that are fully implemented and available in the current release.

| Feature | Doc | GEMMA | TEC | Status |
|---------|-----|-------|-----|--------|
| [Anonymization Pipeline](./anonymization.md) | anonymization.md | Media-behandelingcomponent | TEC-DMS-2 | Done |
| [Consent Management](./consent-management.md) | consent-management.md | Documentregistratiecomponent | TEC-DMS-7 | Done |
| [Publication Consent Process](./publication-consent-process.md) | publication-consent-process.md | Documentregistratiecomponent | TEC-DMS-7 | Done |
| [Metadata Enrichment](./metadata-enrichment.md) | metadata-enrichment.md | Documentregistratiecomponent | TEC-DMS-4, TEC-DMS-9 | Done |
| [Admin Settings](./admin-settings.md) | admin-settings.md | — | TEC-DMS-5 | Done |
| [Dashboard](./dashboard.md) | dashboard.md | — | TEC-DMS-10 | Done |
| [Document Register](./document-register.md) | document-register.md | Documentbeheercomponent | TEC-DMS-4 | Done |
| [PDF Generation](./pdf-generation.md) | pdf-generation.md | Outputmanagementcomponent | TEC-DMS-1 | Done |
| [Template Management](./template-management.md) | template-management.md | Documentcreatiecomponent | TEC-DMS-1 | Done |
| [Prometheus Metrics](./prometheus-metrics.md) | prometheus-metrics.md | — | TEC-DMS-10 | Done |
| [OCR Document Scanning](./ocr-document-scanning.md) | ocr-document-scanning.md | Scanning-en-imagingcomponent | TEC-DMS-2 | Done |
| [Text Extraction](./text-extraction.md) | text-extraction.md | Scanning-en-imagingcomponent | TEC-DMS-2 | Done |
| [Entity Management](./entity-management.md) | entity-management.md | Media-behandelingcomponent | TEC-DMS-2 | Done |
| [Advanced Template Management](./advanced-template-management.md) | advanced-template-management.md | Documentcreatiecomponent | TEC-DMS-1, TEC-DMS-8 | Done |
| [Print Functionality](./print-functionality.md) | print-functionality.md | Outputmanagementcomponent | TEC-DMS-1 | Done |
| [CI/CD Quality Checks](./ci-cd-quality-checks.md) | ci-cd-quality-checks.md | — | — | Done |
| [Backend Services](./backend.md) | backend.md | — | — | Done |
| [Dossier Register](./dossier-register.md) | dossier-register.md | Documentregistratiecomponent | TEC-DMS-4 | Done |

### Planned / In Progress

Features that are specified and actively being developed or reviewed.

| Feature | Doc | GEMMA | TEC | Status |
|---------|-----|-------|-----|--------|
| [Enhanced Anonymization (Batch)](./enhanced-anonymization.md) | enhanced-anonymization.md | Media-behandelingcomponent | TEC-DMS-7 | Proposed |
| [Batch Processing](./batch-processing.md) | batch-processing.md | Media-behandelingcomponent | TEC-DMS-7 | Proposed |
| [Anonymization Entity Review](./anonymization-entity-review.md) | anonymization-entity-review.md | Media-behandelingcomponent | TEC-DMS-7 | Proposed |
| [Print Preview](./print-preview.md) | print-preview.md | Outputmanagementcomponent | TEC-DMS-1 | Reviewed |
| [Letter & Correspondence Generation](./letter-correspondence-generation.md) | letter-correspondence-generation.md | Outputmanagementcomponent | TEC-DMS-1 | Proposed |
| [Document Creation from Templates](./document-creatie-sjablonen.md) | document-creatie-sjablonen.md | Documentcreatiecomponent | TEC-DMS-1 | Proposed |
| [Register i18n](./register-i18n.md) | register-i18n.md | — | TEC-DMS-1 | Proposed |
| [GDPR Anonymization](./gdpr-anonymization.md) | gdpr-anonymization.md | Media-behandelingcomponent | TEC-DMS-7 | Proposed |
| [WCAG Compliance](./wcag-compliance.md) | wcag-compliance.md | — | — | Proposed |

### Future / Roadmap

Features proposed based on tender analysis and customer requests. Not yet specified in detail.

| Feature | Doc | GEMMA | TEC | Status |
|---------|-----|-------|-----|--------|
| [Document Signing](./document-signing.md) | document-signing.md | — | TEC-DMS-4 | Roadmap |
| [Digital Signing Integration](./digital-signing.md) | digital-signing.md | — | TEC-DMS-4 | Roadmap |
| [Document Classification](./document-classification.md) | document-classification.md | Documentregistratiecomponent | TEC-DMS-4 | Roadmap |
| [Document Comparison](./document-comparison.md) | document-comparison.md | Documentbeheercomponent | TEC-DMS-8 | Roadmap |
| [Document Validation](./document-validation.md) | document-validation.md | Documentregistratiecomponent | TEC-DMS-4 | Roadmap |
| [Document Generation](./document-generation.md) | document-generation.md | Outputmanagementcomponent | TEC-DMS-1 | Roadmap |
| [Document Reporting](./document-reporting.md) | document-reporting.md | Documentregistratiecomponent | TEC-DMS-10 | Roadmap |
| [Reports Interface](./reports-interface.md) | reports-interface.md | — | TEC-DMS-10 | Roadmap |
| [External Integration](./external-integration.md) | external-integration.md | — | — | Roadmap |
| [Workflow Automation](./workflow-automation.md) | workflow-automation.md | — | TEC-DMS-7 | Roadmap |

## Adding a New Feature Doc

1. Create `docs/features/{feature-slug}.md` with a Docusaurus frontmatter block (`id`, `title`, `sidebar_label`, `sidebar_position`, `description`).
2. Add a corresponding OpenSpec spec at `openspec/specs/{feature-slug}/spec.md`.
3. Add a row to the appropriate table above with doc link, GEMMA reference, TEC code, and status.
4. Update `project.md` feature table to keep the two sources in sync.

## File Naming Convention

| Suffix | Meaning |
|--------|---------|
| No suffix | Implemented or nearly complete |
| *(Planned)* in title | Specified, not yet implemented |
| *(Roadmap)* in title | Proposed, no detailed spec yet |
