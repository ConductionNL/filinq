## Why

Once `entity-relation-grondslagen` (OpenRegister) lands, every anonymised entity carries its legal bases (grondslagen) on the `EntityRelation` row. That data is currently invisible to operators and auditors — they can see the anonymised file but not the structured "we redacted these entities under these grondslagen" record that compliance reporting and Wet open overheid retention practice require.

This change introduces the rendering subsystem — Twig templates, the `GrondslagenSummaryService`, the per-dossier endpoint, and the auto-regen-on-`checkedOn` listener. The per-document anonymise endpoint's opt-in flag (`appendBasisSummary`) lives in the sibling change `anonymisation-append-basis-summary-flag`.

## What Changes

- **NEW capability:** `anonymisation-grondslagen-summary`. Per-document append (the rendering logic that takes an anonymised PDF + source-file ID and appends a summary page), per-dossier summary endpoint, Twig template set, dossier `configuration.grondslagen.*` fields, auto-regen on `dossier.checkedOn` updates.
- **NEW endpoint:** `POST /api/anonymization/dossier/{dossierId}/grondslagen-pdf` — generates / regenerates the per-dossier summary PDF on demand. Destination: `<dossier-folder>/anonymised/grondslagen.pdf` (per `anonymisation-output-folder-layout`); fallback `<dossier-folder>/grondslagen.pdf` until that change lands.
- **NEW templates:** `lib/Resources/templates/grondslagen/summary_per_doc.twig` and `summary_per_dossier.twig`. NL-only in v1; EN follows when `register-i18n` lands.
- **NEW dossier `configuration` fields:** `grondslagen.fileId`, `grondslagen.lastGeneratedAt`, `grondslagen.autoRegenOnReview` (default true). No schema migration (`configuration` is free-form JSON).
- **NO new schemas, no migrations, no DB changes.** All persistence reuses existing `EntityRelation.bases` (OR), the `base` schema (DocuDesk via `add-dossier-schema`), the dossier object's `configuration` field, and Nextcloud Files.

### Out of scope

- The per-document anonymise-endpoint flag itself (`appendBasisSummary`) — that's `anonymisation-append-basis-summary-flag`.
- Generating summaries automatically on every per-document anonymise — opt-in only.
- Custom layouts / themes / logos / watermarks.
- EN translations of templates — NL-only v1.
- Re-running the summary against historical anonymisations whose `EntityRelation.bases` is null — past data shows "no grondslag recorded" rows; no back-fill.
- Separate audit listing for `acknowledgedOverrides` — covered by the override mechanism in `anonymisation-prohibition-gate`.

## Capabilities

### New Capabilities

- `anonymisation-grondslagen-summary`

## Cross-app Dependencies

- **Hard** — `openregister:entity-relation-grondslagen` — provides `EntityRelation.bases` data.
- **Hard** — `docudesk:add-dossier-schema` — provides the `base` schema and the dossier `configuration` field used to record `fileId` / `lastGeneratedAt`.
- **Soft** — `docudesk:anonymise-output-as-pdf-by-default` — per-document append works cleanest against PDF output.
- **Soft** — `docudesk:anonymisation-output-folder-layout` — provides `<dossier-folder>/anonymised/` destination; fallback to `<dossier-folder>/grondslagen.pdf` until landed.

## Impact

- **Code (docudesk):** `lib/Service/GrondslagenSummaryService.php` (NEW), `lib/Resources/templates/grondslagen/*.twig` (NEW), `lib/Controller/DossierController.php` (NEW endpoint or extension), object-changed listener for the dossier register.
- **API contract:** one new endpoint (`POST .../dossier/{id}/grondslagen-pdf`); auto-regen on `checkedOn` is internal.
- **Privacy / compliance:** Strengthens audit traceability (per-redaction grondslag visible alongside the redacted document). PDF/A-3b output for archival compliance.
- **Migration:** None.
