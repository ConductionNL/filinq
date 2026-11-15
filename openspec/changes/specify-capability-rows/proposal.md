---
kind: code
---

# Proposal: specify-capability-rows

## Why

Every row in `openspec/parity/capabilities.json` should record the
requirement that says it. For 17 filinq rows no requirement in the cited
specs said what the row says, so nobody could check the code against it.
This change writes those requirements. Each one names its row.

## What changes

ADDED requirements, REQ-SCR-001 to REQ-SCR-018, in these capabilities:

- `document-signing`: reminders, the completion notification, delegated signing authority, a phone code for signers.
- `document-register`: sorting the flat file list, documents that wait for me, the audit trail of a document.
- `woo-publicatie-pipeline`: the active publication list, withdrawing a publication when its source is destroyed.
- `management-reports`: the personal data report.
- `anonymization`: business and financial identifiers.
- `redaction-output-guarantee`: the redaction certificate with its grounds.
- `batch-anonymization`: a profile per document type.
- `document-creatie-sjablonen`: marking a due document reviewed.
- `template-management`: draft template versions tried with sample data.
- `portal-contribution`: a citizen's own documents in the portal.
- `keyboard-and-screen-reader-access`: keyboard and screen reader use of every screen.
- `document-merge`: copying a merged PDF or any document to another record (dossiq row 4.14).

Rows that are already built and match their requirement keep `built`.
Rows whose requirement goes past what the code does are set to `planned`
against this change, with what is missing written in their evidence.

## Impact

Specification only. The build tasks are in `tasks.md`.
