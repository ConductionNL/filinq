---
sidebar_position: 3
title: Anonymise a document
description: Run a document through DocuDesk's GDPR-aware anonymisation flow and review the redacted entities before exporting.
---

# Anonymise a document

DocuDesk's anonymisation pipeline detects personal data (names, addresses, BSNs, phone numbers, emails, …) in any uploaded document, lets you review each detection, and produces a redacted copy that is safe to publish under GDPR / Wet Open Overheid.

## Goal

By the end you will have anonymised one document, reviewed the detected entities, accepted or rejected each one, and downloaded the redacted output.

## Prerequisites

- You have completed [Upload a document](./02-upload-document.md) and have at least one document in the **Documents** register.
- The instance has the **Presidio** anonymisation service configured and reachable (an admin sets this in **Settings → DocuDesk → Anonymisation**).

## Steps

1. Open the document detail view and click **Anonymise** in the action bar.

   ![Document detail with Anonymise action](/screenshots/tutorials/user/03-anonymise-document-01.png)

2. DocuDesk extracts entities through the configured anonymisation service. The detection table lists each entity, its type (PERSON, ADDRESS, PHONE_NUMBER, …) and confidence, plus the surrounding text excerpt.

   ![Anonymisation entity review](/screenshots/tutorials/user/03-anonymise-document-02.png)

3. Walk through the detections row by row. For each, choose **Accept** (replace with the entity-type placeholder, e.g. `[PERSON]`), **Reject** (keep the original text), or **Edit** (replace with a custom string).

   ![Reviewing one entity](/screenshots/tutorials/user/03-anonymise-document-03.png)

4. Click **Apply anonymisation**. DocuDesk generates a new redacted document and links it back to the original via the *Source document* field. Download it from the detail view's **Outputs** tab.

   ![Anonymised output ready to download](/screenshots/tutorials/user/03-anonymise-document-04.png)

## Verification

You are done when: the **Outputs** tab on the source document shows one new entry of type *Anonymised*, the file can be downloaded, and opening it shows the accepted entities replaced with the chosen placeholder.

## Common issues

| Symptom | Fix |
|---|---|
| Anonymise action returns *"Service unreachable"* | The Presidio backend isn't running, or its URL in **Settings → DocuDesk → Anonymisation** points at the wrong host. Restart the service and retry. |
| Detection table is empty even on a document you know contains names | The Presidio analyser doesn't recognise the document language — check **Settings → DocuDesk → Anonymisation → Languages** and enable the relevant pack. |
| Output document has text overlapping or layout shifted | PDFs with complex layouts can't be redacted in-place; DocuDesk falls back to a plain-text export. For layout-preserving redaction, convert the source to DOCX first. |

## Reference

- [Anonymisation feature](../../features/anonymization.md) — full reference for the anonymisation flow.
- [Anonymisation entity review](../../features/anonymization-entity-review.md) — UI patterns for reviewing detections.
- [Anonymisation rules (admin)](../admin/02-anonymisation-rules.md) — instance-wide rule configuration.
