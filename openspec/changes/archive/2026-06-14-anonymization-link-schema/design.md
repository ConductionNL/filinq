## Context

DocuDesk delegates all file-level anonymisation to OpenRegister's `FileService::anonymizeDocument()`. After a successful run, `AnonymizationService::anonymizeDocument` returns a `$resultInfo` array containing `anonymizedFileId`, `anonymizedFileName`, `anonymizedFilePath`, and `replacementCount` (derived from `parseAnonymizationResult` + `verifyReplacements`). Today nothing persists the relationship between the source NC file ID and the anonymised file ID: each run is stateless from DocuDesk's perspective.

Consumers (operators, downstream ZAAK/WOO integrations) need to answer two questions:

1. "What is the anonymised version of source file 42?" → filter `sourceFileId = 42`
2. "What is the source of anonymised file 99?" → filter `anonymizedFileId = 99`

OR's search API already supports faceted filtering on any property declared `"facetable": true`. No new infrastructure is needed — only a schema + a service-layer upsert.

The existing `ConsentService::findExistingConsent` (idempotency via `searchObjects` + `@self`-preserving update) and `CorrespondenceService::logCorrespondence` (`saveObject` to `document` register) establish the canonical patterns this change follows exactly.

## Goals / Non-Goals

**Goals:**

- Persist a single `anonymizationLink` OR object per source file, updated idempotently on re-anonymisation.
- Expose both `sourceFileId` and `anonymizedFileId` as facetable properties so OR's search API can answer bidirectional lookup queries.
- Surface `anonymizationLinkId` in the `anonymizeDocument` response body.
- Keep the upsert best-effort: failure must never abort or alter the HTTP response from the anonymisation endpoint.
- Meet the document-register's `hardValidation: true` constraint (ADR-006 + OR Adoption Decision 3).

**Non-Goals:**

- Stale-target reconciliation (cleaning up orphaned anonymised files when the source was moved or renamed between runs). Deferred — see Future Work in proposal.md. This requires an OR-level feature to specify output path at call time.
- A UI screen to browse anonymisation links. Out of scope for this change; the OR generic object browser already surfaces the schema.
- Soft-delete / archival of link records when the source file is deleted. Deferred.

## Decisions

### Decision 1: Store in `document` register, new `anonymizationLink` schema

**Rationale**: The `document` register already holds correspondence audit logs and batch job records. Anonymisation link records are logically part of the document-processing audit trail. Reusing the same register avoids a new register registration and keeps the OR config footprint minimal.

**Alternatives considered**: A new `anonymization` register (rejected — creates register sprawl for a small schema); the `dossier` register (rejected — that register is scoped to folder-level dossiers, not individual file pairs).

### Decision 2: Idempotency key is `sourceFileId` (integer)

**Rationale**: A given NC file ID is stable for the lifetime of the file (it does not change on content updates). Using it as the idempotency key means every re-anonymisation of the same file updates the same OR record rather than accumulating duplicates. `runCount` is incremented on update so the audit trail retains how many times the document was processed.

**Alternatives considered**: A composite key `(sourceFileId, anonymizedFileId)` — rejected because after OR overwrites the anonymised file in place, the NC file ID of the anonymised file can rotate (OR deletes + recreates). Using `sourceFileId` alone is robust to that.

### Decision 3: Upsert via `searchObjects` + `saveObject` (mirrors ConsentService)

**Rationale**: `ConsentService::findExistingConsent` + `updateExistingConsent` already demonstrates the exact pattern: `searchObjects` with `@self` + field filter, then `saveObject` preserving the existing `@self` (which triggers the OR update path). This is the established idiom in DocuDesk; diverging would require reviewers to learn a new pattern.

**Alternatives considered**: `OR\ObjectService::getObjectByField` (if such a method exists) — not used because `searchObjects` is the documented public contract and is already exercised by `ConsentService`.

### Decision 4: Best-effort, never-abort (mirrors `createConsentsForUnredactedEntities` and `attachGrondslagenSummary`)

**Rationale**: The anonymisation run has already succeeded and the anonymised file exists. A link-persistence failure must not retroactively fail that operation. Operators can reconstruct the link from the file system if needed. A `warning` field is NOT added on link failure (unlike `attachGrondslagenSummary`) because the link is a background audit record, not a user-visible output.

### Decision 5: `hardValidation: true` with full `required` + `properties`

**Rationale**: OR Adoption Decision 3 (document-register spec, REQ-DREG-01 and REQ-DREG-ALINK-01) mandates this for all schemas in the `document` register. Only `sourceFileId` and `anonymizedFileId` are declared `required` — all other fields are optional to allow partial creation when some metadata is unavailable.

### Decision 6: Archival retention P7Y (object form); category is a placeholder pending selectielijst sign-off

**Rationale**: Anonymisation link records document a privacy-relevant processing action (GDPR Art. 5(2) accountability). P7Y is the most defensible default — a shorter retention would risk deleting records still needed for data-subject access requests or supervisory authority inquiries.

**Format (discovered at install time)**: OpenRegister's `ArchivalAnnotationValidator` (openregister#1614) requires `x-openregister-archival.retention` to be an **object** with a required ISO-8601 `default` (e.g. `{"retention": {"default": "P7Y"}}`), NOT the bare string `"P7Y"` that the older docudesk schemas still use. `anonymizationLink` therefore ships the object form so it passes schema validation on import. (The pre-existing `correspondence`, `batchCorrespondenceJob`, and `signing*` schemas still use the string form and fail OR validation in current environments — tracked separately, see proposal Future Work.)

### Decision 6b: Bump the `document` register version 1.0.0 → 1.1.0

**Rationale**: OR's `ImportHandler` version-gates register updates (`version_compare(new, existing, '<=')` → skip). Adding a schema to the register's `schemas` array only takes effect on import if the register's own version increases. Without this bump the register would never re-link `anonymizationLink` on any deployment.

**Open item (operator decision)**: the exact `x-openregister-archival.category` selectielijst classification is NOT finalised. Ship a clearly-marked placeholder (e.g. `"TODO: confirm Archiefwet 1995 selectielijst category with selectielijst manager"`) and flag it in tasks.md for the organisation's selectielijst manager to confirm before the change is archived. Do not invent a specific cat. number.

### Decision 7: Write ONLY after a SUCCESSFUL anonymisation run (not at analysis time, not on failure)

**Rationale**: At `extractAndDetectEntities` time there is no anonymised file yet; the link would be incomplete and misleading. The link is only meaningful once `anonymizedFileId` is known, which is after `parseAnonymizationResult` returns. The link record therefore ALWAYS points at a real anonymised file: `recordAnonymizationLink` is invoked only on the success path, so `anonymizedFileId` can stay `required` under `hardValidation: true`. Failed runs are NOT recorded (they are already logged elsewhere), and the `status` field carries only the value `anonymized` (the `failed` value is intentionally not used in this change).

## Risks / Trade-offs

- **OR fileId rotation on re-anonymisation**: OR's `DocumentProcessingHandler` deletes and recreates the anonymised file each run. The new NC fileId is captured in `anonymizedFileId` on every upsert, so the link always points to the current version. The old fileId is silently overwritten — no history of past anonymised file IDs is kept. This is acceptable for v1; a future history table is deferred.
- **`searchObjects` returns stale results**: If OR caches results, a near-concurrent second run could find no existing record and create a duplicate. Mitigation: OR objects in the `document` register have no unique index enforcement at the DB layer, so a duplicate will exist but be harmless (both will be found on the next search; the first candidate wins). The risk is low in practice (re-anonymisation is a manual operator action, not a high-concurrency path).
- **Best-effort failure observability**: If the upsert silently fails (e.g., OR is temporarily unavailable), there is no `anonymizationLinkId` in the response and no UI indicator. Mitigation: the failure is logged at `warning` level; operators monitoring Nextcloud logs will see it.

## Migration Plan

1. Apply phase 1: Update `lib/Settings/docudesk_register.json` — add `anonymizationLink` schema, bump info.version to `5.3.0`.
2. Apply phase 2: Add `recordAnonymizationLink` private method to `AnonymizationService` + call from `anonymizeDocument`.
3. Apply phase 3: Add unit tests.
4. On next Nextcloud boot, `SettingsInitializer::initialize()` detects `5.3.0 > 5.2.0` and calls `ConfigurationService::importFromApp()`. No manual migration step.
5. No rollback needed: the schema addition is additive; removing it again is a further config version bump.

## Seed Data

Per ADR-016, every change that adds an OR schema must include realistic seed objects. The following objects should be inserted under `components.objects` in `docudesk_register.json` for demonstration and testing purposes.

These examples use a **Dutch municipality / consultancy / travel-agency** style organisation, consistent with existing seed data in the register.

**Example 1 — Gemeente Demostad, Woo-verzoek 2025-017 (PDF output, first run)**

```json
{
  "@self": {
    "register": "document",
    "schema": "anonymizationLink",
    "slug": "alink-demostad-beschikking-2024-0042"
  },
  "sourceFileId": 1001,
  "sourceFileName": "beschikking_subsidie_2024-0042.docx",
  "sourceFilePath": "/Demostad/Woo-2025-017/beschikking_subsidie_2024-0042.docx",
  "anonymizedFileId": 1002,
  "anonymizedFileName": "beschikking_subsidie_2024-0042_anonymized.pdf",
  "anonymizedFilePath": "/Demostad/Woo-2025-017/beschikking_subsidie_2024-0042_anonymized.pdf",
  "outputFormat": "pdf",
  "status": "anonymized",
  "replacementCount": 14,
  "runCount": 1,
  "anonymizedAt": "2026-03-14T10:25:00+00:00",
  "anonymizedBy": "woo.officer@demostad.nl"
}
```

**Example 2 — Gemeente Demostad, re-anonymised (runCount incremented)**

```json
{
  "@self": {
    "register": "document",
    "schema": "anonymizationLink",
    "slug": "alink-demostad-bezwaar-wmo-2024-0118"
  },
  "sourceFileId": 1101,
  "sourceFileName": "bezwaarschrift_wmo_2024-0118.docx",
  "sourceFilePath": "/Demostad/WMO-bezwaren-2024/bezwaarschrift_wmo_2024-0118.docx",
  "anonymizedFileId": 1110,
  "anonymizedFileName": "bezwaarschrift_wmo_2024-0118_anonymized.pdf",
  "anonymizedFilePath": "/Demostad/WMO-bezwaren-2024/bezwaarschrift_wmo_2024-0118_anonymized.pdf",
  "outputFormat": "pdf",
  "status": "anonymized",
  "replacementCount": 9,
  "runCount": 2,
  "anonymizedAt": "2026-02-28T16:10:00+00:00",
  "anonymizedBy": "woo.officer@demostad.nl"
}
```

**Example 3 — Conduction B.V. demo, DOCX output (consulting style)**

```json
{
  "@self": {
    "register": "document",
    "schema": "anonymizationLink",
    "slug": "alink-conduction-demo-adviesrapport-2026"
  },
  "sourceFileId": 2001,
  "sourceFileName": "adviesrapport_digitale_transformatie_2026.docx",
  "sourceFilePath": "/Conduction/Demo/adviesrapport_digitale_transformatie_2026.docx",
  "anonymizedFileId": 2002,
  "anonymizedFileName": "adviesrapport_digitale_transformatie_2026_anonymized.docx",
  "anonymizedFilePath": "/Conduction/Demo/adviesrapport_digitale_transformatie_2026_anonymized.docx",
  "outputFormat": "docx",
  "status": "anonymized",
  "replacementCount": 5,
  "runCount": 1,
  "anonymizedAt": "2026-04-01T09:15:00+00:00",
  "anonymizedBy": "demo@conduction.nl"
}
```

**Example 4 — Zonnestraal Reizen, ODT output (travel-agency style)**

```json
{
  "@self": {
    "register": "document",
    "schema": "anonymizationLink",
    "slug": "alink-zonnestraal-klacht-2025-0033"
  },
  "sourceFileId": 3001,
  "sourceFileName": "klachtbrief_zomerseizoen_2025-0033.odt",
  "sourceFilePath": "/Zonnestraal/Klachten-2025/klachtbrief_zomerseizoen_2025-0033.odt",
  "anonymizedFileId": 3002,
  "anonymizedFileName": "klachtbrief_zomerseizoen_2025-0033_anonymized.odt",
  "anonymizedFilePath": "/Zonnestraal/Klachten-2025/klachtbrief_zomerseizoen_2025-0033_anonymized.odt",
  "outputFormat": "odt",
  "status": "anonymized",
  "replacementCount": 7,
  "runCount": 1,
  "anonymizedAt": "2026-05-12T11:40:00+00:00",
  "anonymizedBy": "privacy.officer@zonnestraal.nl"
}
```

> All four seed objects use `status: "anonymized"` — this change records only successful runs (Decision 7), so there is no `failed` seed object.

## Open Questions

None — all decisions resolved with information available at design time. See `DEFERRED_QUESTIONS` in the generating agent's output for provisional choices made during artifact creation.
