# Grondslagen Summary

Adds two opt-in rendering surfaces that turn the structured grondslagen (Woo Art. 5 legal bases) stored on each `EntityRelation` row by Wave 1.3 (`entity-relation-grondslagen`) into a human-readable audit trail. The first appends a per-document summary page to an anonymised PDF; the second produces a per-dossier summary PDF aggregating the same data across every file in a dossier.

Both surfaces are pure renderers. No new schemas, no new persistence beyond the existing dossier object's `configuration` JSON field, which records the generated file's id and timestamp so the dossier UI can badge the report as fresh.

## Per-document summary

Triggered by the operator when running the anonymise endpoint with `appendBasisSummary: true`:

```http
POST /apps/docudesk/api/anonymization/anonymize/{fileId}
Content-Type: application/json

{
  "entities": [ ... ],
  "appendBasisSummary": true
}
```

Behaviour:

- **PDF output** (the default once `anonymise-output-as-pdf-by-default` ships, and the typical case today when the source is already a PDF): the summary is rendered as a single extra page and appended to the anonymised PDF in place via FPDI. The anonymised file's bytes are replaced atomically with the merged PDF.
- **Native-format output** (operator opted out via `outputFormat: "preserve"`, e.g. DOCX in → DOCX out): the summary is written as a separate `<base>_grondslagen.pdf` file in the same folder.
- **Failure non-fatal**: any rendering / merge / write failure is logged at warning level and surfaces as a `warning` field on the anonymise response. The anonymised file itself is preserved.

Response (success, PDF output):

```json
{
  "anonymizedFileId": 1234,
  "anonymizedFileName": "verslag_anonymized.pdf",
  "anonymizedFilePath": "/admin/dossier/verslag_anonymized.pdf",
  "replacementCount": 7,
  "summaryAppended": true
}
```

Response (success, separate-PDF fallback):

```json
{
  "anonymizedFileId": 1234,
  "anonymizedFileName": "verslag_anonymized.docx",
  "anonymizedFilePath": "/admin/dossier/verslag_anonymized.docx",
  "replacementCount": 7,
  "summaryAppended": false,
  "summaryFileId": 1235,
  "summaryFilePath": "/admin/dossier/verslag_anonymized_grondslagen.pdf"
}
```

Response (warning on summary failure):

```json
{
  "anonymizedFileId": 1234,
  "anonymizedFileName": "verslag_anonymized.pdf",
  "anonymizedFilePath": "/admin/dossier/verslag_anonymized.pdf",
  "replacementCount": 7,
  "warning": "grondslagen_summary_failed: <error message>"
}
```

The batch / folder anonymise endpoints accept the same flag and surface the per-file outcome (warning / summary file id) in the multi-file response shape.

## Per-dossier summary

Generated on demand:

```http
POST /apps/docudesk/api/anonymization/dossier/{dossierUuid}/grondslagen-pdf
```

Walks every file under the dossier's folder (recursing into subfolders, but skipping conventional `anonymised/` / `redacted/` subfolders that hold redacted outputs), reads each file's anonymised `EntityRelation` rows via OR, aggregates per-document and per-grondslag tables, and writes the resulting PDF to `<dossier-folder>/grondslagen.pdf`. The dossier object's `configuration.grondslagen.{fileId, lastGeneratedAt}` is updated on success.

Response:

```json
{
  "fileId": 9001,
  "filename": "grondslagen.pdf",
  "filePath": "/admin/dossier/grondslagen.pdf",
  "size": 18432,
  "generatedAt": "2026-05-15T14:32:11+00:00"
}
```

The summary PDF is fixed PDF/A-3b output (mPDF native; no FPDI merge step on this path).

### Auto-regeneration on dossier review

When the dossier's `checkedOn` timestamp is updated (the operator's "I've reviewed this dossier" action), the same render fires automatically as part of the event-handler chain. Opt-out per dossier via:

```json
{
  "configuration": {
    "grondslagen": {
      "autoRegenOnReview": false
    }
  }
}
```

Regen failure on the auto-regen path is logged at warning level — the dossier update itself is never rolled back.

## Content of the summaries

### Per-document page

- Header: filename, anonymisation timestamp, operator user, anonymisation tool ("OpenAnonymiser via OpenRegister").
- Table per anonymised entity: entity text, type (`PERSON` / `ORGANIZATION` / `OTHER`), the placeholder it was replaced with, the bases (resolved to `base.name` from the `dossier` register).
- Footer: total entity count + distinct grondslagen count.

### Per-dossier PDF

- Header: dossier name, description, last `checkedOn`, generation timestamp.
- **Per document**: filename, anonymised entity count, bases used (with per-basis count).
- **Per grondslag**: basis name, number of documents that used it, total entities anonymised under it.
- Footer: aggregate totals.

The summary lists ONLY entities whose `EntityRelation.anonymized` is `true` and whose `bases[]` is non-null. Entities released via `acknowledgedOverrides` (`anonymisation-grondslagen-and-prohibition-gate`) are NOT listed — the report is "what was redacted under what grondslag", not "what the operator chose not to redact".

## Dossier configuration fields

The dossier object's `configuration` JSON field (free-form per `add-dossier-schema`) gains the following conventional keys — no schema migration required:

| Key | Type | Default | Purpose |
|---|---|---|---|
| `configuration.grondslagen.fileId` | int / null | null | Nextcloud node id of the latest summary PDF. |
| `configuration.grondslagen.lastGeneratedAt` | ISO-8601 / null | null | UTC timestamp of the latest render. The dossier UI uses this for a freshness badge. |
| `configuration.grondslagen.autoRegenOnReview` | bool | true | When `true`, a `checkedOn` change triggers an auto-regen. Set to `false` to opt out at the dossier level. |

## Limitations (v1)

- **NL-only templates.** EN follows when `register-i18n` lands.
- **PDF/A-3b applies only to the per-dossier output.** The per-document append uses FPDI to merge pages from the anonymised PDF + the summary PDF; the upstream PDF's PDF/A conformance isn't guaranteed, so the merged output isn't strictly PDF/A. The per-dossier path uses pure mPDF and is PDF/A-3b.
- **Manual edits inside the rendered summary file aren't preserved across regen.** Operators should treat the rendered PDF as a regeneratable artifact; the source of truth is the EntityRelation rows in OR.
- **Empty bases.** Entities anonymised by a pre-Wave-1.3 detector pass have `bases: null`. They appear in the summary with a "geen grondslag geregistreerd" note rather than being silently omitted.

## Cross-change dependencies

| Change | Dep type | Reason |
|---|---|---|
| `openregister:entity-relation-grondslagen` (Wave 1.3) | Hard | Provides `EntityRelation.bases` + the `findAnonymisedEntitiesWithBasesForFile` mapper method this feature reads from. |
| `docudesk:add-dossier-schema` (Wave 1.1) | Hard | Provides the `dossier` register, the `base` schema for label resolution, and the dossier object's `configuration` JSON field used for freshness metadata. |
| `docudesk:anonymise-output-as-pdf-by-default` (Wave 4b) | Soft | When PDF is the default output, the per-document append always succeeds in-place. Until 4b lands, native-format outputs fall back to the separate-PDF path. |
| `docudesk:anonymisation-output-folder-layout` (Wave 2) | Soft | Once shipped, both per-document and per-dossier summary destinations move into `<source-folder>/anonymised/`. Until then, the flat in-folder path is used. |
