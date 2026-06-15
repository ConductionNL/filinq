# Grondslagen Summary

DocuDesk generates PDF/A-3b grondslagen (legal-basis) summaries in two forms:

1. **Per-document** — an extra summary page appended to an anonymised PDF, listing every redacted entity and the legal basis under which it was anonymised.
2. **Per-dossier** — a standalone summary PDF aggregating grondslag data across all files in a dossier, generated on demand or automatically when the dossier is reviewed.

## Dependency

Both surfaces require `openregister:entity-relation-grondslagen` to be active — that change adds the `bases` column to `EntityRelation` rows. Without it, the summaries render with `⟨geen grondslag vastgelegd⟩` placeholders for all entities.

## Per-document summary (appendSummaryToPdf)

When the anonymise endpoint is called with `appendBasisSummary: true` and `outputFormat: "pdf"` (the default), `GrondslagenSummaryService::appendSummaryToPdf()` is invoked after the anonymised file has been written to Nextcloud.

The service:
1. Loads anonymised entities for the file (`EntityRelation.anonymized = true`).
2. Resolves each entity's `bases` UUIDs to human-readable names from the `base` schema.
3. Renders `summary_per_doc.twig` via `PdfService` (Twig + mPDF).
4. Opens the existing anonymised PDF with mPDF + FPDI, imports all source pages, appends a new summary page, and replaces the file content in Nextcloud atomically.

For `outputFormat: "preserve"`, `appendSummaryAsSeparatePdf()` is called instead: the summary is saved as `<original-base>_anonymized_grondslagen.pdf` alongside the preserve-mode file. The anonymise endpoint response gains `summaryFileId` and `summaryFilePath` fields.

## Per-dossier summary endpoint

```
POST /api/anonymization/dossier/{dossierId}/grondslagen-pdf
```

Authenticated, no request body. Resolves the dossier via OpenRegister, walks all files under `@self.folder`, aggregates per-document and per-grondslag data, renders `summary_per_dossier.twig`, and writes the result to:

- **Primary path** (when `anonymised/` subfolder exists): `<dossier-folder>/anonymised/grondslagen.pdf`
- **Fallback path**: `<dossier-folder>/grondslagen.pdf`

The response is HTTP 200 with the updated dossier object (including `configuration.grondslagen.*` fields).

An empty dossier (no anonymised files) still produces a valid near-empty PDF (header + empty tables + zero totals).

## Auto-regen on dossier `checkedOn` update

`DossierCheckedOnListener` subscribes to `ObjectUpdatedEvent` for dossier objects. When `checkedOn` changes and `configuration.grondslagen.autoRegenOnReview` is `true` (the default), `renderDossierSummary()` is called synchronously. If rendering fails, the error is logged and the dossier update still succeeds — operators can retry via the on-demand endpoint.

## Dossier `configuration.grondslagen` fields

| Field | Type | Description |
|---|---|---|
| `fileId` | `int\|null` | Nextcloud file ID of the last generated summary PDF |
| `lastGeneratedAt` | `string\|null` | ISO-8601 timestamp of the last successful generation |
| `autoRegenOnReview` | `bool` | Whether to auto-regen on `checkedOn` update (default `true`) |

No schema migration is required — `configuration` is a free-form JSON field on the dossier object.

## Template customisation

Templates are Twig files at `lib/Resources/templates/grondslagen/`:

- `summary_per_doc.twig` — per-document summary page
- `summary_per_dossier.twig` — per-dossier standalone PDF

Both use Dutch labels (NL-only in v1; EN follows when `register-i18n` lands). Templates run in the same Twig sandbox as the rest of DocuDesk.

## Cross-change dependencies

| Change | Type | Why |
|---|---|---|
| `openregister:entity-relation-grondslagen` | **Hard** | Provides `EntityRelation.bases` data |
| `docudesk:add-dossier-schema` | **Hard** | Provides the `base` schema and the `configuration` field |
| `docudesk:anonymise-output-as-pdf-by-default` | Soft | Per-document append works cleanest on PDF/A-3b output |
| `docudesk:anonymisation-output-folder-layout` | Soft | Provides the `anonymised/` subfolder destination |
