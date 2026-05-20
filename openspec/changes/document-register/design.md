## Context

DocuDesk's anonymization pipeline uses OpenRegister to persist analysis results. `AnonymizationService` calls `ObjectService::saveObject()` with a report payload that names the `document` register and `report` schema. However, because `SettingsService::initialize()` never imports `document_register.json`, the register and schemas do not exist in OpenRegister — saves fail or land in an inconsistent state.

The JSON file `lib/Settings/document_register.json` is otherwise complete:
- Register definition: slug `document`, version `0.0.1`
- Three schemas: `report`, `template`, `entity` (all `hardValidation: false`, `properties: []`)
- Three realistic sample objects from a real anonymization pipeline run

The only missing pieces are (a) the boot-time import call, and (b) the `anonymization` schema referenced by sample objects 2 and 3 but absent from the schema list.

OpenRegister already provides the entire data layer:
- `ConfigurationService::importFromApp()` → `ImportHandler::importFromApp()` handles register + schema creation and idempotent seed-object upsert.
- `ObjectService::saveObject()` handles all CRUD for report objects.
- `AuditTrailService` provides automatic change tracking at zero cost.
- `TextExtractionService` + `EntityRecognitionHandler` produce the data that populates reports.

So the work in this change is not "build a storage layer". It is "wire up the existing JSON file into the boot path and declare the missing schema."

## Goals / Non-Goals

**Goals:**
- Import `document_register.json` during app initialisation so the register, four schemas, and seed objects are available after every install / upgrade.
- Add the `anonymization` schema declaration to eliminate the sample-object schema inconsistency.
- Document the expected report object shape (for code clarity), even though `hardValidation: false` means no schema-level enforcement.
- Provide 3 seed objects that demonstrate the full anonymization pipeline output (already in the JSON; need to be loadable).

**Non-Goals:**
- Schema properties on `template`, `entity`, or `anonymization` — deliberately deferred; these schemas are placeholders with ad-hoc fields.
- WCAG compliance, language level, retention period, or GDPR data controller fields — planned separately.
- Cross-document entity linking — the `entity` schema is a skeleton; its property shape is TBD.
- A UI for browsing report objects — covered by OpenRegister's generic object browser.
- File hash upgrade from MD5 to SHA-256 — separate follow-up; annotated in standards references.

## Decisions

### D1. `hardValidation: false` and `properties: []` are intentional for all schemas

Different document types (DOCX, PDF, EML, ODT) produce different entity shapes. A strict schema would either reject valid analysis output or force every document type to conform to a lowest-common-denominator. Keeping `hardValidation: false` lets the pipeline evolve without register migrations.

**Trade-off:** Field documentation lives in this spec, not the schema. Developers must read the spec to know the expected payload shape.

**Alternative considered:** Define properties on `report` schema. Rejected because the pipeline occasionally writes additional ad-hoc fields (e.g. per-extractor metadata) that would fail strict validation.

### D2. `anonymization` schema is declared, not removed

Sample objects 2 and 3 (UUIDs `c04e1fa9` and `685c5b5c`) carry the full replacement-map output of the anonymization pipeline — valuable for QA, demos, and debugging. Deleting them would remove the only in-system demonstration of the entity-replacement operation. Adding the `anonymization` schema declaration (same `hardValidation: false` convention) makes the register internally consistent at zero cost.

**Alternative considered:** Migrate sample objects 2 and 3 to use the `report` schema. Rejected: these objects have a fundamentally different shape (they carry `replacements` maps, not file metadata), so forcing them into `report` would misrepresent their purpose.

### D3. Import via existing repair step, not a new boot hook

`docudesk_register.json` is already loaded by the `RegistersLoader` repair step. Adding `document_register.json` to the same step follows the established pattern (ADR-013), keeps the boot path simple, and leverages the existing idempotency logic (`version_compare`-based skip, slug-based upsert matching).

**Alternative considered:** Load both files in `SettingsService::initialize()` called from `Application::boot()`. Rejected: repair steps have controlled execution order and run on upgrade; boot hooks run on every request and are inappropriate for slow data-layer operations.

### D4. Seed objects are the existing three pipeline samples, unchanged

The three sample objects in `document_register.json` are realistic: they come from an actual test run against `test_ano.docx`. They cover three meaningful states:
1. Original document — Critical risk, 7 entities
2. Anonymization operation output — replacement map
3. Re-analysis of anonymized file — High risk (replacement tokens still detected by NER)

These satisfy ADR-016's "3–5 realistic objects per schema" requirement for the `report` and `anonymization` schemas. `template` and `entity` have no seed objects (their purpose is TBD).

## Risks / Trade-offs

**[MD5 hash weakness]** — `fileHash` uses MD5 (RFC 1321), which is cryptographically broken for collision resistance. For file integrity verification in an archival context, SHA-256 is the recommended upgrade. Risk is accepted in v1 since the pipeline already writes MD5; a later change can migrate to SHA-256 and verify old hashes opportunistically.
→ Mitigation: annotated in standards references; tracked as a follow-up.

**[Replacement token re-detection]** — Sample 3 shows that NER detects the 8-char hex replacement tokens as entities (riskScore 77.2, riskLevel "High"). This is a known limitation of running NER on already-anonymized text. The limitation is documented in the spec; no fix is in scope here.
→ Mitigation: sample 3 exists specifically to demonstrate this limitation to developers and QA.

**[Empty template and entity schemas]** — `template` and `entity` have no defined properties. DocuDesk code that tries to query them by field will produce empty result sets or unexpected behaviour.
→ Mitigation: both schemas are marked "Planned" in the spec requirements table; they are placeholders, not live features.

**[Idempotency assumption on import]** — The `force: false` import path matches by slug. If a slug collision exists with another app's register, the import is silently skipped.
→ Mitigation: slug `document` is namespaced to DocuDesk by convention. No other app currently registers a `document` slug.

## Seed Data

Per ADR-016, the following seed objects ship inside `document_register.json` under `components.objects`. All three are realistic pipeline outputs from a Dutch-language test run.

### `report` schema — seed object 1 (Original document analysis)

```json
{
  "@self": {
    "register": "document",
    "schema": "report",
    "slug": "report-test-ano-original"
  },
  "nodeId": 42,
  "filePath": "/DocuDesk/test_ano.docx",
  "fileName": "test_ano.docx",
  "fileType": "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
  "fileExtension": "docx",
  "fileSize": 13545,
  "status": "completed",
  "errorMessage": null,
  "riskScore": 97.85,
  "riskLevel": "Critical",
  "anonymizationResults": [],
  "entities": [
    {"text": "Ruben van der Linde", "score": 0.9993, "entityType": "PERSON"},
    {"text": "Conduction B.V.", "score": 0.9871, "entityType": "ORGANIZATION"},
    {"text": "Jan de Vries", "score": 0.9912, "entityType": "PERSON"},
    {"text": "Gemeente Amsterdam", "score": 0.9654, "entityType": "ORGANIZATION"},
    {"text": "Maria Janssen", "score": 0.9788, "entityType": "PERSON"},
    {"text": "Pieter Bakker", "score": 0.9651, "entityType": "PERSON"},
    {"text": "Anna Smit", "score": 0.9432, "entityType": "PERSON"}
  ],
  "fileHash": "a3f2c1d4e5b6789012345678abcdef01",
  "text": "Geachte heer Van der Linde, namens Conduction B.V. wil ik u informeren..."
}
```

### `anonymization` schema — seed object 2 (Anonymization operation output)

```json
{
  "@self": {
    "register": "document",
    "schema": "anonymization",
    "slug": "anonymization-test-ano-result"
  },
  "sourceNodeId": 42,
  "targetNodeId": 43,
  "sourceFilePath": "/DocuDesk/test_ano.docx",
  "targetFilePath": "/DocuDesk/test_ano_anonymized.docx",
  "replacements": {
    "Ruben van der Linde": "4a7f2c9e",
    "Conduction B.V.": "b3d8e1f5",
    "Jan de Vries": "c2a9f0d4",
    "Gemeente Amsterdam": "e5b3c7a2",
    "Maria Janssen": "f1d6b8e3",
    "Pieter Bakker": "a8e2f4c1",
    "Anna Smit": "d0c5b9f7"
  },
  "status": "completed"
}
```

### `report` schema — seed object 3 (Re-analysis of anonymized document)

```json
{
  "@self": {
    "register": "document",
    "schema": "report",
    "slug": "report-test-ano-anonymized"
  },
  "nodeId": 43,
  "filePath": "/DocuDesk/test_ano_anonymized.docx",
  "fileName": "test_ano_anonymized.docx",
  "fileType": "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
  "fileExtension": "docx",
  "fileSize": 13210,
  "status": "completed",
  "errorMessage": null,
  "riskScore": 77.2,
  "riskLevel": "High",
  "anonymizationResults": [],
  "entities": [
    {"text": "4a7f2c9e", "score": 0.8912, "entityType": "PERSON"},
    {"text": "b3d8e1f5", "score": 0.8721, "entityType": "ORGANIZATION"},
    {"text": "c2a9f0d4", "score": 0.8634, "entityType": "PERSON"}
  ],
  "fileHash": "d7e8f9012345678abcdef0123456789a",
  "text": "Geachte heer 4a7f2c9e, namens b3d8e1f5 wil ik u informeren..."
}
```

Note: Seed object 3 demonstrates the known NER limitation — replacement tokens (hex strings) are still detected as entities, producing a residual risk score of 77.2 / High. This is expected and intentional in the seed data.

## Reuse Analysis

Per ADR-012 (Deduplication), the following existing OpenRegister services are leveraged — no new equivalents are built:

| Concern | Existing provider | DocuDesk adds |
|---|---|---|
| Register + schema creation | `ConfigurationService::importFromApp()` | JSON file only |
| Object CRUD | `ObjectService::saveObject()`, `findObjects()` | Nothing |
| Seed object loading | `ImportHandler::importFromApp()` via repair step | Entry in repair step |
| Audit trail | `AuditTrailService` (automatic) | Nothing |
| File text extraction | `TextExtractionService` | Nothing |
| NER entity detection | `EntityRecognitionHandler` | Nothing |
| Idempotency | `version_compare` skip in `ImportHandler` | Nothing |

No OpenRegister service is duplicated.

## Migration Plan

1. Add `anonymization` schema to `lib/Settings/document_register.json` under `components.schemas`.
2. Add `document_register.json` import call in the `RegistersLoader` repair step alongside the existing `docudesk_register.json` import.
3. On `occ maintenance:repair` (or fresh install), `ConfigurationService::importFromApp()` creates the `document` register, four schemas, and three seed objects.
4. Re-running on an already-configured instance: the loader matches existing objects by slug and skips; existing report objects in the register are preserved.
5. Rollback: remove the `document_register.json` import call from the repair step. Existing register/schema/object rows in OpenRegister are not automatically deleted (manual cleanup via admin UI if required).

## Open Questions

- **`template` schema purpose** — the `document` register's `template` schema overlaps with the `template` schema in `docudesk_register.json` (which has properties defined). Should the document-register `template` be removed, or kept as a placeholder for a future document-level template binding? Provisional: keep as placeholder with a clarifying `description` field; follow-up change to either populate or remove.
- **SHA-256 migration** — when should `fileHash` be upgraded from MD5 to SHA-256? The pipeline already generates and stores MD5 hashes for existing report objects. A migration that recomputes old hashes would require re-reading every analyzed file. Deferred; track in a separate change.
- **`anonymization` schema long-term home** — the `anonymization` schema was created to fix the sample inconsistency. Is it the right long-term schema for anonymization operation output, or should that live in the `consent` register alongside existing anonymization-related consent objects? No decision needed in this change; the schema remains in `document` for now.
