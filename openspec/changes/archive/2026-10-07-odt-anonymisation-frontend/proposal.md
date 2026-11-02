---
kind: code
depends_on:
  - openregister:odt-anonymisation-writeback
---

## Why

The Filinq anonymisation upload widget hard-blocks `.odt` (OpenDocument Text) files. `AnonymizationWidget.vue` restricts uploads to `docx`/`txt`/`pdf`/`eml` via its `accept` attribute, an `ALLOWED_EXTENSIONS`/`ALLOWED_MIMES` allow-list, and matching user-facing copy — so a user cannot select an ODT for anonymisation even though the rest of the stack now handles it. The allow-list was originally deliberately narrow because ODT anonymisation silently no-opped or corrupted the file in OpenRegister. That backend gap is closed by the paired `openregister:odt-anonymisation-writeback` change, which redacts ODT in place (structure preserved) with a fail-loud validation gate. This change opens the front door so users can actually anonymise ODT.

This is the frontend half of the ODT-support pair. It MUST land after the backend fix so users are never routed to the (now-fixed) bug before it ships.

## What Changes

- **MODIFIED:** `src/views/anonymization/AnonymizationWidget.vue` accepts `.odt`. The upload allow-list and `partitionFiles()` logic are extracted into a new pure module `src/services/anonymizationUpload.js` (matching the repo's `src/services/*.js` + `.spec.js` pattern) so the allow-list is unit-testable; `odt` and `application/vnd.oasis.opendocument.text` are added to `ALLOWED_EXTENSIONS`, `ALLOWED_MIMES`, and the file input's `accept` attribute.
- **MODIFIED:** The two user-facing copy strings that enumerate supported formats now include ODT ("Only Word (.docx), ODT, PDF or TXT files are supported…" and the skipped-files variant), with NL + EN translations added to `l10n/`.
- **NEW:** `src/services/anonymizationUpload.spec.js` — Jest tests asserting ODT is accepted by MIME and by extension, the previously-supported formats still pass, and unsupported formats are still rejected.
- **NO backend change** — ODT redaction lives entirely in `openregister:odt-anonymisation-writeback`. No API, store, or route changes here.

## Capabilities

### Modified Capabilities

- `anonymization` — the upload surface now accepts ODT inputs alongside docx/txt/pdf/eml.

## Impact

- **Affected code:** `src/views/anonymization/AnonymizationWidget.vue`, `src/services/anonymizationUpload.js` (new), `src/services/anonymizationUpload.spec.js` (new), `l10n/{en,nl}.{js,json}`.
- **Cross-app dependency:** requires `openregister:odt-anonymisation-writeback` deployed so ODT uploads actually redact. Ship after it.
- **No new dependency, no schema, no HTTP surface change.**
- **i18n:** two new strings, NL + EN provided (ADR-005/i18n).
