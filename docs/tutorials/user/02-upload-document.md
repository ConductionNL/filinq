---
sidebar_position: 2
title: Upload a document
description: Upload a file into DocuDesk so it appears in the Documents register and can be anonymised, templated, signed or retained.
---

# Upload a document

DocuDesk treats every file as a typed Document object stored in the OpenRegister **Documents** register. Uploading a file kicks off the metadata-enrichment pipeline (language detection, keyword extraction, topic classification) and makes the file available to every other DocuDesk flow.

## Goal

By the end you will have uploaded one document, watched the metadata enrichment fill in, and inspected the resulting Document object.

## Prerequisites

- You have completed [Open DocuDesk for the first time](./01-first-launch.md).
- You have a sample document on disk — PDF, DOCX or plain text work; image-only files are accepted but won't trigger language detection without OCR.

## Steps

1. From the navigation, click **Documents** and then **Upload**.

   ![Documents list with Upload button](/screenshots/tutorials/user/02-upload-document-01.png)

2. Pick a file from your local disk. The upload dialog shows file name, size and detected MIME type before you confirm.

   ![Upload dialog](/screenshots/tutorials/user/02-upload-document-02.png)

3. Submit the upload. The document appears in the list with a *Processing…* badge while the metadata-enrichment pipeline runs in the background.

   ![Document being processed](/screenshots/tutorials/user/02-upload-document-03.png)

4. After processing (usually a few seconds for short documents), the badge clears. Click the row to open the detail view. The right-hand panel shows the enriched metadata — detected language, top keywords, topic classification.

   ![Document detail with enriched metadata](/screenshots/tutorials/user/02-upload-document-04.png)

## Verification

You are done when: the document appears in the **Documents** list, the *Processing…* badge has cleared, and the detail view shows at least *Language* and *Topic* fields populated.

## Common issues

| Symptom | Fix |
|---|---|
| Upload fails with "MIME type not allowed" | The instance admin restricts allowed types in **Settings → DocuDesk → Allowed MIME types** — ask them to extend the list. |
| *Processing…* badge never clears | The metadata-enrichment service (Tika / Solr / language-detection) is unreachable — check **Settings → DocuDesk → Service status**. |
| Detail view shows empty *Language* / *Topic* fields | The document is too short for reliable detection (under ~200 words) or contains only scanned images — try OCR before upload. |

## Reference

- [Document register feature](../../features/document-register.md) — full reference for the Document object.
- [Metadata enrichment feature](../../features/metadata-enrichment.md) — what the enrichment pipeline does.
