# Processing Activity Register & Export (AVG Art. 30)

## Overview

Under AVG (GDPR) Article 30, the controller must maintain a record of processing
activities (*verwerkingsregister*). DocuDesk performs systematic, large-scale
processing of personal data — anonymisation, OCR, metadata enrichment, and
digital signing.

The Art. 30 register, per-access logging (*verwerkingenlogging*), the export
engine (JSON/CSV/PDF), the no-literal-PII output contract, and the admin/FG
access gating are **provided by OpenRegister** as a platform capability
(`openregister/processing-activity-register`, OR-PA-1..9 — the 2026-06-11
abstraction decision). DocuDesk is a **thin consumer**: it contributes the
document-processing activity catalogue, the grondslag/retention mapping, and an
admin UI window onto the platform register. DocuDesk ships **no** aggregation
service, export controller, or register template of its own (ADR-022).

## What DocuDesk contributes

### The four processing activities (catalogue)

DocuDesk declares its four processing activities as `x-openregister-processing`
catalogue annotations in `lib/Settings/docudesk_register.json`. Each annotation
also opts its carrying schema into OpenRegister's per-access read-logging
(`logReads: true`) and attributes reads to its own activity code.

| Activity | Code | Carrying schema | Backend | Retention reference |
|---|---|---|---|---|
| Anonymisation of documents | `docudesk-anonymisation` | `anonymizationLink` | `docudesk.anonymiser` | P7Y (selectielijst category to be confirmed) |
| OCR text extraction | `docudesk-ocr` | `generatedDocument` | `docudesk.ocr.tesseract` | not declared |
| Document metadata enrichment | `docudesk-metadata-enrichment` | `base` | `docudesk.metadata.enricher` | not declared |
| Digital document signing | `docudesk-signing` | `signingAuditEntry` | `docudesk.signing` | P10Y (Archiefwet 1995 selectielijst cat. 5.1.3) |

Retention references are taken from each schema's existing
`x-openregister-archival` annotation; where a schema carries none, the
reference reads **"not declared"** rather than being omitted, so the gap stays
visible in the register.

### Grondslag (legal basis) mapping

The legal-bases dimension sources from `EntityRelation.bases[]` (OpenRegister).
Relations whose `bases` is null or absent appear in OpenRegister's explicit
unclassified / `no-grondslag-recorded` bucket (OR-PA-4) and are never dropped
from totals.

### Admin UI surfacing

The DocuDesk admin settings (`/settings/admin/docudesk`) gain an **AVG Art. 30
processing-activity register** compliance section that:

- shows the OpenRegister-maintained controller-identity record with a configure
  prompt when it has not been set (the export still succeeds with identity
  fields rendered as "not configured");
- lists the four declared activities with their purpose and retention;
- deep-links to OpenRegister's per-access processing log scoped to DocuDesk's
  registers, and to the per-subject (*betrokkene*) inzage extract.

Access follows OpenRegister's model (OR-PA-8): administrators and the configured
privacy-officer (FG) group only; non-admins are denied. The compliance section
itself is rendered only for admins.

## Capability status (honest)

The per-access **read log** and **per-subject (betrokkene) extract** are
available now (OpenRegister >= 0.2.14: `ProcessingLogService`,
`ProcessingLogController` at `/api/avg/verwerkingen[/betrokkene]`, FG/admin
gated, fail-closed, range-bounded, no cross-tenant IDOR).

The following pieces are **deferred in OpenRegister** and therefore not yet
end-to-end from DocuDesk:

- the full register-import **catalogue seeder** that upserts the four activities
  as drafts by code (OR-PA-2.2 — only a lazy per-org fallback activity is seeded
  today). Until it lands, attribution falls through to OpenRegister's
  `niet-geclassificeerde-verwerking` bucket rather than to the named activities.
- the aggregate **Art. 30 export** to JSON/CSV/PDF with the no-literal-PII byte
  contract (OR-PA-5.3/OR-PA-7 — `Art30ExportService`). The read-log query
  surface is the available interim.
- the OpenRegister verwerkingsregister **UI** (OR-PA-7.1).

DocuDesk's contribution (catalogue annotations + read-logging opt-in + admin
window) is complete; the items above are tracked on the OpenRegister side.

## Art. 30 mapping table

| Art. 30(1) element | Source |
|---|---|
| (a) Controller identity | OpenRegister controller-identity record (OR-PA-1) |
| (b) Purposes of processing | `doelbinding` per activity (this catalogue) |
| (c) Categories of data subjects / personal data | `dataCategories` (NER entity types) per activity |
| (d) Recipients | OpenRegister (out of scope for DocuDesk) |
| (e) Transfers to third countries | OpenRegister |
| (f) Retention periods | `x-openregister-archival` annotations (mirrored as `retentionReference`) |
| (g) Security measures | OpenRegister |
| Legal basis (Art. 6) | `EntityRelation.bases[]` (OR-PA-4) |

## References

- OpenSpec change: `openspec/changes/processing-activity-export/`
- OpenRegister capability: `openregister/processing-activity-register` (OR-PA-1..9)
- Register annotations: `lib/Settings/docudesk_register.json`
- Admin UI: `src/views/settings/Settings.vue`
