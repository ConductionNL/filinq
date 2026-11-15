---
kind: code
---

# Proposal: finish-half-built-capabilities

## Summary

The closing audit of 12 November 2026 read every capability row marked `built` against the code. Seven rows describe work whose backend exists but whose capability does not reach the user, or reaches only part of what the row asks. Under decision 389 those rows lose `built` and need a plan. This change is that plan for the rows that have no other open change in their area.

## What changes

- **in-ocr**: the recognised text is written back into the file as a text layer, so the file itself becomes searchable.
- **det-scans**: the OCR fallback hands its text to OpenRegister through the seam OpenRegister now ships (`extractFromProvidedText(ProvidedTextRequest)`). Filinq still calls it with two positional arguments, so the call throws, is caught, and every scan stays `ocrDetectionPending`.
- **woo-consent**: a consent request is sent to the third party. Today a request is recorded and its period tracked, but nothing is sent ("Notification dispatch MUST stay stubbed in v1").
- **gen-lists**: the correspondence generator and the template preview pass `listRefs`, so a table in a template can be filled from related records.
- **wk-final-rule**: a consuming app or an admin can declare a finality rule from a screen or a documented call, so the rule reaches documents.
- **sig-order**: the signing mode and signer order are read when a signer signs, so sequential signing is enforced.

## Out of scope

Rows that already have an open change in their area are planned there: in-scan-split (scan-intake-with-separator-sheets), in-party (intake-base-registry-enrichment), in-number and rec-download-alert (documents-in-and-out-of-the-building), con-flow (flow-operations), sig-cancel (signing-cancellation), sh-letter (letter-email-sending), rec-audit (specify-capability-rows).
